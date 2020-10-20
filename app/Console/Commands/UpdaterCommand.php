<?php

namespace App\Console\Commands;

use App\Sensor;
use App\SensorData;
use App\SensorDataOld;
use Illuminate\Console\Command;

class UpdaterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updater:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sensors = Sensor::all();
        $sensorsIdsByName = [];

        foreach($sensors as $sensor) {
            $sensorsIdsByName[$sensor->name] = $sensor->id;
        }

        SensorDataOld::orderBy('created_at', 'ASC')->chunk(500, function($data) use ($sensorsIdsByName) {
            $newData = [];

            foreach($data->all() as $dataPoint) {
                $newData[] = [
                    'device_id' => 1,
                    'sensor_id' => $sensorsIdsByName[$dataPoint->sensor],
                    'value' => $dataPoint->value,
                    'sensored_at' => $dataPoint->sensored_at,
                    'created_at' => $dataPoint->created_at
                ];
            }

            SensorData::insert($newData);
            $this->info('Inserted one chunk of 500.');
        });
        
        return 0;
    }
}
