<?php

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
    Route::get('/graph', 'GraphController@show');

    Route::get('/sensorData', 'SensorDataController@index');
    // Route::post('/sensorData', 'SensorDataController@store');

    Route::post('/sensorData', 'SensorDataController@store')->middleware('auth:device');
});
