<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/key', function() {
    return \Illuminate\Support\Str::random(32);
});

$router->post('/1xbet/api/makedeposit', "Bet1xController@makeDeposit");

$router->post('/goxi/api/makedeposit', "GoxiController@makeDeposit");

$router->post('/1xbet/api/monnify/topup', "BetController@index");

$router->post('/1xbet/api/updatedb', "BetdbController@update");

$router->post('/1xbet/api/getnumber', "BetdbController@number");

# Universal

$router->get("/universal/api/{txId}", "UniversalController@home");


