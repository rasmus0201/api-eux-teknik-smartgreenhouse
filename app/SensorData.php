<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    /**
     * The timestamps
     * 
     * @var array
     */
    public $timestamps = [
        'created_at'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sensor_id', 'value', 'sensored_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sensored_at' => 'datetime',
    ];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }
}
