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

$router->post('/api/monnify/topup', "MonnifyController@index");


$router->post('/1xbet/api/updatedb', "BetdbController@update");

$router->post('/1xbet/api/getnumber', "BetdbController@number");

# Universal
$router->get("/universal/api/{txId}", "UniversalController@home");
$router->get("/universal/api/newpolicy/{phone}", "UniversalController@newPolicy");
$router->get("/universal/api/renewpolicy/{phone}", "UniversalController@renewPolicy");

# EasyPay
$router->post("/easypay/api/customer/create", "EasyPayController@createNewCustomer");
$router->get("/easypay/api/customer/{phone}", "EasyPayController@getUserDetails");

# BulkSms Api
$router->post("/novaji/bulk-sms/send", "NovajiBulkSMSController@send");

$router->get("/test/http/request", "NovajiBulkSMSController@makeRequest");

$router->get("/route-mobile", "NovajiBulkSMSController@getBalanceRouteMobile");

$router->get("/route-mobile/balance", "NovajiBulkSMSController@getBalance");

# 1xbet MI Billing Api
$router->get("/1xbet/billing", "BillingController@index");
