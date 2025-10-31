<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentUpload;
use App\Services\DocumentAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    private DocumentAnalysisService $analysisService;
    private array $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'];

    public function __construct(DocumentAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    private function getMemberId()
    {
        $token = session('member_access_token') ?? request()->cookie('member_access_token');

        if (!$token) {
            throw new \Exception('No authentication token');
        }

        $tokenRecord = \DB::table('member_token')
            ->where('type', 1)
            ->where('value', $token)
            ->first();

        if (!$tokenRecord) {
            throw new \Exception('Invalid authentication token');
        }

        return $tokenRecord->member_id;
    }

    public function upload(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|max:10240']);
            $file = $request->file('file');
            $memberId = $this->getMemberId();

            $this->validateFile($file);

            // Check if this file has already been uploaded by this user
            $fileHash = md5_file($file->getRealPath());
            $existingDocument = DocumentUpload::where('member_id', $memberId)
                ->where('file_hash', $fileHash)
                ->first();

            if ($existingDocument) {
                $analysisResult = $existingDocument->analysis_result;
                if (is_string($analysisResult)) {
                    $analysisResult = json_decode($analysisResult, true) ?? [];
                }

                return $this->documentResponse($existingDocument, $analysisResult);
            }

            $storedFileName = Str::random(32) . '.' . strtolower($file->getClientOriginalExtension());
            $uploadPath = 'upload/member_chat_uploads';
            $fileSize = $file->getSize();

            // Store file
            if (!is_dir(public_path($uploadPath))) {
                @mkdir(public_path($uploadPath), 0755, true);
            }
            $file->move(public_path($uploadPath), $storedFileName);
            $filePath = "{$uploadPath}/{$storedFileName}";

            $document = DocumentUpload::create([
                'member_id' => $memberId,
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename' => $storedFileName,
                'file_path' => $filePath,
                'file_type' => strtolower($file->getClientOriginalExtension()),
                'file_size' => $fileSize,
                'file_hash' => $fileHash,
                'status' => 'processing',
            ]);

            // Extract and analyze
            try {
                $extractedText = $this->analysisService->extractText(public_path($filePath), $document->file_type);
            } catch (\Exception $e) {
                $document->markFailed('Extraction error: ' . $e->getMessage());
                Log::error('Document extraction error', ['file' => $filePath, 'error' => $e->getMessage()]);
                // The error message is already user-friendly from DocumentAnalysisService
                return $this->errorResponse('extraction_error', $e->getMessage(), 422);
            }

            if (empty(trim($extractedText))) {
                $document->markFailed('No text extracted');
                Log::warning('No text could be extracted from document', ['file' => $filePath, 'type' => $document->file_type]);
                return $this->errorResponse('extraction_failed', 'Could not extract text from this document. The file may be empty, encoded, or in an unsupported format.', 422);
            }

            $document->update(['extracted_text' => $extractedText]);
            $analysisResult = $this->analysisService->analyzeText($extractedText, 'comprehensive');
            $document->markCompleted($analysisResult);

            // Save analysis to chat_log so it appears in chat history
            try {
                $targetDate = (int)date('Ymd'); // Must be integer format like 20251022
                $nowUtc = \Carbon\Carbon::now('UTC')->toDateTimeString();

                $analysisText = is_array($analysisResult) ? ($analysisResult['result'] ?? '') : (string)$analysisResult;

                $askId = \DB::table('chat_log')->insertGetId([
                    'member_id'   => $memberId,
                    'related_id'  => 0,
                    'target_date' => $targetDate,
                    'type'        => 'ask',
                    'content'     => "📄 " . $file->getClientOriginalName(),
                    'status'      => 1,
                    'created_at'  => $nowUtc,
                    'updated_at'  => $nowUtc,
                ]);
                \DB::table('chat_log')->where('id', $askId)->update(['related_id' => $askId]);

                if (!empty($analysisText)) {
                    \DB::table('chat_log')->insertGetId([
                        'member_id'   => $memberId,
                        'related_id'  => $askId,
                        'target_date' => $targetDate,
                        'type'        => 'reply',
                        'content'     => $analysisText,
                        'status'      => 1,
                        'created_at'  => $nowUtc,
                        'updated_at'  => $nowUtc,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to save document analysis to chat_log: ' . $e->getMessage());
                // Continue anyway - analysis is still saved in document_uploads table
            }

            return $this->documentResponse($document, $analysisResult);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('validation_error', 'Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error('Document upload error', ['error' => $e->getMessage()]);
            return $this->errorResponse('upload_error', $e->getMessage(), 500);
        }
    }

    private function validateFile($file)
    {
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'text/plain',
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('File type not supported');
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $this->allowedExtensions)) {
            throw new \Exception('File extension not supported');
        }
    }

    public function show($documentId)
    {
        try {
            $document = DocumentUpload::findOrFail($documentId);
            return $this->documentResponse($document, $document->analysis_result, [
                'document' => array_merge($this->formatDocument($document, true)),
            ]);
        } catch (\Exception $e) {
            Log::error('Get document error', ['error' => $e->getMessage()]);
            return $this->errorResponse('fetch_error', $e->getMessage(), 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $query = DocumentUpload::query();

            if ($request->has('member_id')) {
                $query->where('member_id', $request->member_id);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $total = $query->count();
            $documents = $query->orderBy('created_at', 'desc')
                ->limit(min($request->limit ?? 20, 100))
                ->offset($request->offset ?? 0)
                ->get()
                ->map(fn($doc) => array_merge(
                    $this->formatDocument($doc, true),
                    ['type' => $doc->file_type]  // Alias for 'file_type'
                ));

            return response()->json([
                'documents' => $documents,
                'total' => $total,
            ], 200);
        } catch (\Exception $e) {
            Log::error('List documents error', ['error' => $e->getMessage()]);
            return $this->errorResponse('list_error', $e->getMessage(), 500);
        }
    }

    public function delete($documentId)
    {
        try {
            $document = DocumentUpload::findOrFail($documentId);

            if ($document->file_path && file_exists(public_path($document->file_path))) {
                unlink(public_path($document->file_path));
            }

            $document->delete();

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('Delete document error', ['error' => $e->getMessage()]);
            return $this->errorResponse('delete_error', $e->getMessage(), 500);
        }
    }

    public function reanalyze($documentId)
    {
        try {
            $document = DocumentUpload::findOrFail($documentId);

            if (!$document->extracted_text) {
                throw new \Exception('No extracted text available');
            }

            $document->markProcessing();
            $analysisResult = $this->analysisService->analyzeText($document->extracted_text, 'comprehensive');
            $document->markCompleted($analysisResult);

            return response()->json([
                'success' => true,
                'analysis' => $analysisResult,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Re-analyze error', ['error' => $e->getMessage()]);
            return $this->errorResponse('reanalyze_error', $e->getMessage(), 500);
        }
    }

    private function errorResponse($error, $message, $status, $details = null)
    {
        $response = ['error' => $error, 'message' => $message];
        if ($details) {
            $response['details'] = $details;
        }
        return response()->json($response, $status);
    }

    private function formatDocument($document, $includeStatus = false)
    {
        $data = [
            'id' => $document->id,
            'filename' => $document->original_filename,
            'file_type' => $document->file_type,
            'file_size' => $document->file_size,
        ];

        if ($includeStatus) {
            $data['status'] = $document->status;
            $data['created_at'] = $document->created_at->toIso8601String();
        }

        return $data;
    }

    private function documentResponse($document, $analysis = null, $additionalData = [])
    {
        $response = [
            'success' => true,
            'document' => $this->formatDocument($document),
        ];

        if ($analysis !== null) {
            $response['analysis'] = $analysis;
        }

        return response()->json(array_merge($response, $additionalData), 200);
    }
}
