<?php

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('v1')->group(function () {
    Route::get('/test', function() {
        $sensors = ['humidity', 'temperature', 'light', 'sound', 'vibration'];

        $dataPoints = 100;
        $data = [];
        for ($i=0; $i < $dataPoints; $i++) { 
            $data[] = [
                'sensor' => Arr::random($sensors),
                'value' => rand(0, 1023),
                'sensored_at' => round(microtime(true) * 1000)
            ];
        }
        
        return response()->json([
            'data' => $data
        ]);
    });
    
    Route::get('/sensors', 'SensorDataController@index');
    Route::get('/graph', 'SensorDataController@graph');
    Route::post('/sensors', 'SensorDataController@create');
});