<?php

use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\RouteMapping;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');


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

Route::any('{segments?}', [App\Http\Controllers\RouteMapping::class, 'index'])->where('segments', '^(?!stripe)([0-9a-zA-Z_\-\/]+)?$');


