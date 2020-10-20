<?php

namespace App\Http\Controllers;

use App\Sensor;
use App\SensorData;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Log;

class GraphController extends Controller
{
    public function show(Request $request)
    {
        if ($request->get('start') && $request->get('end')) {
            $start = Carbon::createFromTimestamp($request->get('start'));
            $end = Carbon::createFromTimestamp($request->get('end'));
        } else {
            $start = Carbon::now()->subDays(30)->setTime(0, 0, 0);
            $end = Carbon::now()->setTime(23, 59, 59);
        }

        $start->setSeconds(0);
        $deviceId = $request->get('device_id') ?? 1;
        $deltaSeconds = 60 * ((int) ($request->get('delta') ?? 60 * 24));

        if ($start >= $end || $deltaSeconds < 1) {
            return response()->json([
                'status' => true,
                'data' => []
            ]);
        }

        $data = [];
        $sensors = Sensor::all();
        $sensorsArr = [];
        foreach($sensors as $sensor) {
            $sensorsArr[$sensor->id] = [
                'device_id' => $deviceId,
                'sensor_id' => $sensor->id,
                'sensor' => $sensor->name,
                'avg' => 0
            ];
        }

        $sensorNamesById = array_combine(array_column($sensorsArr, 'sensor_id'), array_column($sensorsArr, 'sensor'));

        $timestamps = [];
        $currentTs = $start->clone();
        while($currentTs < $end) {
            $timestamps[$currentTs->timestamp] = $currentTs->clone()->addSeconds($deltaSeconds)->timestamp;
            $currentTs->addSeconds($deltaSeconds);
        }

        $dataPoints = SensorData::
            select([
                'sensor_id',
                'sensored_at',
                DB::raw('AVG(value) as value')
            ])
            ->where('device_id', $deviceId)
            ->where('sensored_at', '>=', $start)
            ->where('sensored_at', '<', $end)
            ->groupBy(['sensor_id', 'sensored_at'])
            ->orderBy('sensored_at', 'DESC')
            ->cursor();

        $data = [];
        foreach ($dataPoints as $dataPoint) {
            $ts = $dataPoint->sensored_at->setSeconds(0)->timestamp;
            $tsOffset = $ts % (60 * 60 * 24);
            $intervalTs = ($ts - $tsOffset) + ($tsOffset - ($tsOffset % $deltaSeconds));

            if (!isset($timestamps[$intervalTs])) {
                Log::debug("Could not figure out a correct timestamp! data: ".json_encode([
                    'datapoint_ts' => $ts,
                    'start_ts' => $start->timestamp,
                    'delta_seconds' => $deltaSeconds,
                    'calculated_ts' => $intervalTs
                ]));

                continue;
            }

            if (!isset($data[$intervalTs])) {
                $data[$intervalTs] = [];
            }

            if (!isset($data[$intervalTs][$dataPoint->sensor_id])) {
                $data[$intervalTs][$dataPoint->sensor_id] = [];
            }

            $data[$intervalTs][$dataPoint->sensor_id][] = $dataPoint->value;
        }

        $returnData = [];
        foreach ($data as $intervalTs => $sensorCollections) {
            $dataPoints = [];
            foreach ($sensorCollections as $sensorId => $sensorCollection) {
                $dataPoints[$sensorId] = [
                    'device_id' => $deviceId,
                    'sensor_id' => $sensorId,
                    'sensor' => $sensorNamesById[$sensorId],
                    'avg' => round(array_sum($sensorCollection) / count($sensorCollection), 2)
                ];
            }

            $missingSensors = array_diff_key($sensorsArr, $dataPoints);
            $returnData[$intervalTs] = array_merge($dataPoints, $missingSensors);
        }

        return response()->json([
            'status' => true,
            'data' => $returnData
        ]);
    }
}
