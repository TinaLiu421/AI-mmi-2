<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VisaRequirementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VisaRequirementController extends Controller
{
    private VisaRequirementService $visaService;

    public function __construct(VisaRequirementService $visaService)
    {
        $this->visaService = $visaService;
    }

    /**
     * Get visa requirements for a specific visa type
     *
     * GET /api/visa-requirements/{visa_type}
     * GET /api/visa-requirements?visa_type=temporary%20graduate%20visa%20485&format=json
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRequirements(Request $request)
    {
        // Get visa type from URL parameter or query string
        $visaType = $request->route('visa_type') ?? $request->query('visa_type');
        $format = $request->query('format', 'json'); // json, html, markdown

        if (empty($visaType)) {
            return response()->json([
                'error' => 'visa_type is required',
                'usage' => [
                    'GET /api/visa-requirements/{visa_type}',
                    'GET /api/visa-requirements?visa_type=temporary%20graduate%20visa%20485&format=json'
                ]
            ], 422);
        }

        try {
            $tag = $request->query('tag', 'visa');
            $requirements = $this->visaService->getVisaRequirements($visaType, $tag);

            if (!$requirements['found']) {
                return response()->json($requirements, 404);
            }

            $criteriaTable = $this->visaService->createCriteriaTable($requirements);

            // Format response based on requested format
            if ($format === 'html') {
                $html = $this->visaService->formatCriteriaTableAsHtml($criteriaTable);
                return response()->view('visa-requirements', ['html' => $html], 200)
                    ->header('Content-Type', 'text/html; charset=utf-8');
            } elseif ($format === 'markdown') {
                $markdown = $this->visaService->formatCriteriaTableAsMarkdown($criteriaTable);
                return response()->make($markdown, 200)
                    ->header('Content-Type', 'text/markdown; charset=utf-8');
            }

            // Default: JSON format
            return response()->json([
                'visa_type' => $requirements['visa_type'],
                'requirements' => $requirements,
                'criteria_table' => $criteriaTable
            ]);
        } catch (\Throwable $e) {
            Log::error('VisaRequirementController::getRequirements failed', [
                'visa_type' => $visaType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to retrieve visa requirements',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get requirements for multiple visa types
     *
     * POST /api/visa-requirements/batch
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMultipleRequirements(Request $request)
    {
        $visaTypes = $request->input('visa_types', []);

        if (!is_array($visaTypes) || empty($visaTypes)) {
            return response()->json([
                'error' => 'visa_types array is required',
                'example' => [
                    'visa_types' => [
                        'temporary graduate visa 485',
                        'skilled migration visa 189'
                    ]
                ]
            ], 422);
        }

        try {
            $tag = $request->input('tag', 'visa');
            $multipleRequirements = $this->visaService->getMultipleVisaRequirements($visaTypes, $tag);
            $tables = $this->visaService->createMultipleCriteriaTables($multipleRequirements);

            return response()->json([
                'total' => count($visaTypes),
                'results' => $multipleRequirements,
                'criteria_tables' => $tables
            ]);
        } catch (\Throwable $e) {
            Log::error('VisaRequirementController::getMultipleRequirements failed', [
                'visa_types' => $visaTypes,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to retrieve visa requirements',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get criteria table for a visa type
     *
     * GET /api/visa-requirements/{visa_type}/criteria-table
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCriteriaTable(Request $request)
    {
        $visaType = $request->route('visa_type');
        $format = $request->query('format', 'json'); // json, html, markdown

        if (empty($visaType)) {
            return response()->json([
                'error' => 'visa_type is required'
            ], 422);
        }

        try {
            $tag = $request->query('tag', 'visa');
            $requirements = $this->visaService->getVisaRequirements($visaType, $tag);

            if (!$requirements['found']) {
                return response()->json([
                    'error' => 'Visa requirements not found'
                ], 404);
            }

            $criteriaTable = $this->visaService->createCriteriaTable($requirements);

            // Format response
            if ($format === 'html') {
                $html = $this->visaService->formatCriteriaTableAsHtml($criteriaTable);
                return response()->make($html, 200)
                    ->header('Content-Type', 'text/html; charset=utf-8');
            } elseif ($format === 'markdown') {
                $markdown = $this->visaService->formatCriteriaTableAsMarkdown($criteriaTable);
                return response()->make($markdown, 200)
                    ->header('Content-Type', 'text/markdown; charset=utf-8');
            }

            return response()->json($criteriaTable);
        } catch (\Throwable $e) {
            Log::error('VisaRequirementController::getCriteriaTable failed', [
                'visa_type' => $visaType,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to retrieve criteria table',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
