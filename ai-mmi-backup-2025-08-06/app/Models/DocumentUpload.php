<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentUpload extends Model
{
    protected $table = 'document_uploads';
    protected $guarded = [];
    public $timestamps = true;

    protected $fillable = [
        'member_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_type',
        'file_size',
        'file_hash',
        'extracted_text',
        'analysis_result',
        'status',
        'error_message',
    ];

    protected $casts = [
        'analysis_result' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function markProcessing()
    {
        $this->update(['status' => 'processing']);
    }

    public function markCompleted($analysisResult)
    {
        $this->update([
            'status' => 'completed',
            'analysis_result' => $analysisResult,
            'error_message' => null,
        ]);
    }

    public function markFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
