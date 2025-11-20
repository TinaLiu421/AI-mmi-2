<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseApplication extends Model
{
    use HasFactory;

    protected $table = 'course_applications';

    protected $fillable = [
        'member_id',
        'family_name',
        'given_name',
        'email_address',
        'mobile_number',
        'residential_address',
        'date_of_birth',
        'nationality',
        'highest_education',
        'has_english_test',
        'english_tests',
        'has_financial_support',
        'financial_notes',
        'target_institution',
        'target_program',
        'start_year',
        'wants_scholarship',
        'scholarship_colleges',
        'document_paths',
        'status',
        'payment_status',
        'payment_reference',
        'submitted_at',
    ];

    protected $casts = [
        'english_tests' => 'array',
        'scholarship_colleges' => 'array',
        'document_paths' => 'array',
        'has_financial_support' => 'boolean',
        'has_english_test' => 'boolean',
        'wants_scholarship' => 'boolean',
        'submitted_at' => 'datetime',
    ];
}
