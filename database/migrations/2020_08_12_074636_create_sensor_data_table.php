<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSensorDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sensor_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('device_id');
            $table->unsignedTinyInteger('sensor_id');
            $table->double('value', 8, 2);
            $table->timestamp('sensored_at', 6);
            $table->timestamp('created_at', 6)->nullable();

            $table->index(['device_id', 'sensor_id', 'sensored_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sensor_data');
    }
}
