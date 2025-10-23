<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RagController;
use App\Http\Controllers\Api\DocumentController;

Route::middleware('api')->group(function () {
    Route::post('/rag/ask',  [RagController::class, 'ask'])->name('rag.ask');

    // Document upload and analysis endpoints
    Route::post('/documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');
    Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents', [DocumentController::class, 'list'])->name('documents.list');
    Route::delete('/documents/{id}', [DocumentController::class, 'delete'])->name('documents.delete');
    Route::post('/documents/{id}/reanalyze', [DocumentController::class, 'reanalyze'])->name('documents.reanalyze');

    // Visa requirements endpoints
    Route::get('/visa-by-country', function (Request $request) {
        $country = $request->query('country');

        if (!$country) {
            return response()->json(['status' => 400, 'message' => 'Country is required']);
        }

        $visas = \App\Models\Visa::where('country', $country)
            ->where('status', 'active')
            ->with('criteria')
            ->get();

        if ($visas->isEmpty()) {
            return response()->json(['status' => 404, 'message' => 'No visas found for this country']);
        }

        $visasData = [];
        foreach ($visas as $visa) {
            $visasData[] = [
                'id' => $visa->id,
                'country' => $visa->country,
                'visa_type' => $visa->visa_type,
                'title' => $visa->title,
                'source_url' => $visa->source_url,
                'last_updated' => $visa->last_updated_at,
                'criteria' => $visa->criteria->map(function ($c) {
                    return [
                        'key' => $c->key,
                        'label' => $c->label,
                        'content' => $c->content,
                        'confidence_level' => $c->confidence_level
                    ];
                })->toArray()
            ];
        }

        return response()->json([
            'status' => 200,
            'visas' => $visasData,
            'count' => count($visasData)
        ]);
    })->name('visa.by-country');
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
