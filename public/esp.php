<?php

require_once __DIR__ . '/../esp32/bootstrap/app.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    return esp_abort(404);
}

$apiKey = sanitize_input($_REQUEST['api_key'] ?? '');
if ($apiKey !== $_ENV['API_KEY']) {
    return esp_abort(403, 'Forbidden');
}

// Create connection
try{
    $conn = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch(PDOException $e) {
    esp_abort(500, 'Server error');
}

$lines = explode("\n", file_get_contents('php://input'));

$columnNameMap = [
    'did' => 'device_id',
    'sid' => 'sensor_id',
    'v' => 'value',
    't' => 'sensored_at',
];

$columnTypeMap = [
    'device_id' => 'integer',
    'sensor_id' => 'integer',
    'value' => 'float',
    'sensored_at' => 'DateTime',
];

$result = [];
foreach ($lines as $line) {
    $columns = explode(',', $line);

    $datapoint = [];

    foreach ($columns as $column) {
        list($column, $value) = explode(':', $column);
        $value = sanitize_input($value);
        $column = $columnNameMap[sanitize_input($column)];

        if ($columnTypeMap[$column] == 'DateTime') {
            $value = (new DateTime())->setTimestamp($value)->format('Y-m-d H:i:s.u');
        } else {
            settype($value, $columnTypeMap[$column]);
        }

        $datapoint[$column] = $value;
    }

    $result[] = $datapoint;
}

$columns = array_merge(array_values($columnNameMap), ['created_at']);
$prep = [];
$values = [];
foreach($result as $index => $row) {
    $prepedRow = [];

    foreach ($row as $column => $value) {
        $prepedRow[':'.$column.$index] = $value;
    }

    $prepedRow[':created_at'.$index] = (new DateTime())->format('Y-m-d H:i:s');

    $prep[] = "(" . implode(', ', array_keys($prepedRow)) . ")";
    $values[] = $prepedRow;
}

$stmt = $conn->prepare(
    "INSERT INTO sensor_data (" . implode(', ', $columns) . ") VALUES " . implode(", ", $prep)
);

if (!$stmt) {
    return esp_abort(422, 'Unprocessable data');
}

try {
    $result = $stmt->execute(
        call_user_func_array('array_merge', $values)
    );
} catch (\Throwable $th) {
    return esp_abort(422, 'Unprocessable data');
}
