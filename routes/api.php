<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentApiController;
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

Route::group(['namespace' => 'App\Api\v1\Controllers'], function () {
    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('users', ['uses' => 'UserController@index']);
    });
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::get('/validate-token', [AuthController::class, 'validateToken']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    // Vos routes API protégées
    // Route::get('/projects', [ProjectController::class, 'index']);
    // Route::get('/projects/{external_id}', [ProjectController::class, 'show']);
    // Route::get('/tasks', [TaskController::class, 'index']);
    // Route::get('/tasks/{external_id}', [TaskController::class, 'show']);
    // Route::get('/offers', [OfferController::class, 'index']);
    // Route::get('/offers/{externalId}', [OfferController::class, 'show']);
    // Route::get('/invoices', [InvoiceController::class, 'index']);
    // Route::get('/invoices/{externalId}', [InvoiceController::class, 'show']);
    // Route::get('/payments', [PaymentApiController::class, 'index']);
    // Route::get('/payments/{externalId}', [PaymentApiController::class, 'show']);
});
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{external_id}', [ProjectController::class, 'show']);
Route::get('/tasks', [TaskController::class, 'index']);
Route::get('/tasks/{external_id}', [TaskController::class, 'show']);
Route::get('/offers', [OfferController::class, 'index']);
Route::get('/offers/{externalId}', [OfferController::class, 'show']);
Route::get('/invoices', [InvoiceController::class, 'index']);
Route::get('/invoices/{externalId}', [InvoiceController::class, 'show']);
Route::get('/payments', [PaymentApiController::class, 'index']);
Route::get('/payments/{externalId}', [PaymentApiController::class, 'show']);
Route::put('/payments/{externalId}/amount', [PaymentApiController::class, 'updateAmount']);
Route::delete('/payments/{externalId}', [PaymentApiController::class, 'destroy']);

Route::get('/settings/discount', 'App\Http\Controllers\DiscountSettingController@getDiscountSetting');
// Route::get('/settings/discount', [DiscountSettingController::class, 'getDiscountSetting']);
// Route::post('/settings/discount', [DiscountSettingController::class, 'updateDiscountSetting']);
Route::post('/settings/discount', 'App\Http\Controllers\DiscountSettingController@updateDiscountSetting');

Route::prefix('dashboard')->group(function () {
    Route::get('/', 'App\Http\Controllers\Api\DashboardController@index');
    Route::get('/revenue', 'App\Http\Controllers\Api\DashboardController@revenueData');
    Route::get('/projects', 'App\Http\Controllers\Api\DashboardController@projectStatusData');
    Route::get('/conversion', 'App\Http\Controllers\Api\DashboardController@conversionRateData');
    Route::get('/invoices', 'App\Http\Controllers\Api\DashboardController@invoiceStatusData');
    Route::get('/totals', 'App\Http\Controllers\Api\DashboardController@totals');
});


