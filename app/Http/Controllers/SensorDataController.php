<?php

namespace App\Http\Controllers;

use App\Sensor;
use App\SensorData;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SensorDataController extends Controller
{
    private $prPage = 100;

    private $columnNamesMap = [
        'sid' => 'sensor_id',
        'v' => 'value',
        't' => 'sensored_at',
    ];

    private $columnTypesMap = [
        'sid' => 'integer',
        'v' => 'float',
        't' => 'timestamp',
    ];

    public function index()
    {
        return response()->json(
            SensorData::simplePaginate($this->prPage)
        );
    }

    public function store(Request $request)
    {
        $lines = collect(explode("\n", $request->getContent()));

        if ($lines->count() === 0) {
            return;
        }

        $sensors = Sensor::select(['id'])->get()->pluck('id');

        $sensorData = $lines
            ->map(function($line) use ($sensors) {
                $dataPoints = collect(explode(',', $line));

                $data = $dataPoints->mapWithKeys(function($dataPoint) {
                    $exploded = explode(':', $dataPoint);

                    if (count($exploded) !== 2) {
                        return [];
                    }

                    list($column, $value) = $exploded;

                    if (!isset($this->columnNamesMap[$column]) || !isset($this->columnTypesMap[$column])) {
                        return [];
                    }

                    return [
                        $this->columnNamesMap[$column] => $this->typeConverter($column, $value)
                    ];
                });

                // Some missing columns
                $columnDiff = array_diff(array_values($this->columnNamesMap), $data->keys()->toArray());
                if (count($columnDiff) > 0) {
                    return [];
                }

                // No such sensor
                if (!$sensors->contains($data['sensor_id'])) {
                    return [];
                }

                // Threshold for sensor date
                if (!$data['sensored_at']->between(Carbon::now()->subHours(4), Carbon::now()->addHours(4))) {
                    return [];
                }

                $data['device_id'] = Auth::user()->id;
                $data['created_at'] = Carbon::now();

                return $data;
            })
            ->filter()
            ->values();

        SensorData::insert($sensorData->toArray());
    }

    private function typeConverter($column, $value)
    {
        switch ($this->columnTypesMap[$column]) {
            case 'timestamp':
                return Carbon::createFromTimestampMs($value, 'UTC');
            case 'float':
                return floatval($value);

            case 'integer':
            default:
                return intval($value);
        }
    }
}
