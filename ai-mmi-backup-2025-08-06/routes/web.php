<?php

use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\RouteMapping;
use App\Http\Controllers\Web\Posts as WebPosts;
use App\Http\Controllers\Web\Agent_chat as AgentChatController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');
Route::get('/posts/details/{postId}', [WebPosts::class, 'details'])->name('posts.details');
Route::post('/posts/{postId}/qa-ask', [WebPosts::class, 'qaAsk']);
// Streaming endpoint for AI responses handled by controller
use App\Http\Controllers\Web\Home as HomeController;
Route::post('/chat/stream', [HomeController::class, 'chatStream'])->name('chat.stream');

// Agent chat routes
Route::get('/agent_chat', [AgentChatController::class, 'index']);
Route::get('/agent_chat/{targetId}', [AgentChatController::class, 'index']);
Route::get('/agent_chat/messages/{targetType}/{targetId}', [AgentChatController::class, 'messages']);
Route::get('/agent_chat/threads', [AgentChatController::class, 'threads']);
Route::post('/agent_chat/send', [AgentChatController::class, 'send']);

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



