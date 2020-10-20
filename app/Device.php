<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Device extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'api_token', 'location',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'api_token',
    ];

    public function sensorData()
    {
        return $this->hasMany(SensorData::class);
    }
}
