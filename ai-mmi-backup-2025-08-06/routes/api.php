<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Api\RagController;
use App\Http\Controllers\Api\DocumentController;

Route::middleware('api')->group(function () {
    // Route::post('/rag/ask',  [RagController::class, 'ask'])->name('rag.ask');

    // Document upload and analysis endpoints
    Route::post('/documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');
    Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents', [DocumentController::class, 'list'])->name('documents.list');
    Route::delete('/documents/{id}', [DocumentController::class, 'delete'])->name('documents.delete');
    Route::post('/documents/{id}/reanalyze', [DocumentController::class, 'reanalyze'])->name('documents.reanalyze');
});



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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
