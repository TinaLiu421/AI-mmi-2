<?php

use Illuminate\Support\Facades\Route;

// routes/web.php
// use App\Http\Controllers\StripeWebhookController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::any('{segments?}', [App\Http\Controllers\RouteMapping::class, 'index'])->where('segments','[0-9a-zA-Z_\-\/]+');

// Route::view('/pay/success', 'pay_success')->name('pay.success');
// Route::view('/pay/cancel',  'pay_cancel')->name('pay.cancel');
// Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
//      ->name('stripe.webhook'); // Webhook 回调入口

