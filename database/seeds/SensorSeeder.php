<?php

use App\Sensor;
use Illuminate\Database\Seeder;

class SensorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Sensor::create(['name' => 'temperature']);   
        Sensor::create(['name' => 'humidity']);   
        Sensor::create(['name' => 'light']);   
        Sensor::create(['name' => 'sound']);   
    }
}
