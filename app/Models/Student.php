<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = [
        'user_id', 'lrn', 'school_year', 'is_manually_edited', 'grade_level', 'section_id',
        'sex', 'age', 'place_of_birth', 'mother_tongue',
        'curr_house_number', 'curr_street', 'curr_barangay', 'curr_city', 'curr_province', 'curr_zip_code', 'curr_country',
        'is_perm_same_as_curr', 'perm_house_number', 'perm_street', 'perm_barangay', 'perm_city', 'perm_province', 'perm_zip_code', 'perm_country',
        'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_contact_number',
        'father_last_name', 'father_first_name', 'father_middle_name', 'father_contact_number',
        'guardian_last_name', 'guardian_first_name', 'guardian_middle_name', 'guardian_contact_number'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function kioskEnrollment(): HasOne
    {
        return $this->hasOne(KioskEnrollment::class);
    }

    /**
     * CHANGED TO HASMANY
     * Since the sync now saves a new record for every submission (Version 1, 2, 3...)
     */
    public function preEnrollments(): HasMany
    {
        return $this->hasMany(PreEnrollment::class);
    }

    /**
     * HELPER: Get only the most recent submission
     * This is useful for your main dashboard or profile view.
     */
    public function latestSubmission(): HasOne
    {
        return $this->hasOne(PreEnrollment::class)->latestOfMany();
    }

    /**
     * The single source of truth for the active school year.
     */
    public static function activeYear()
    {
        return \Illuminate\Support\Facades\Cache::remember('active_school_year', 3600, function() {
            $settings = \App\Models\CustomForm::latest()->first();
            return $settings ? $settings->school_year : '2025-2026';
        });
    }

    /**
     * Scope to filter by the authoritative active school year.
     */
    public function scopeInActiveYear($query, $year = null)
    {
        return $query->where('students.school_year', $year ?: static::activeYear());
    }

    /**
     * Returns a subquery of the latest pre_enrollment IDs per student.
     * Useful for joinSub in DB::table queries.
     */
    public static function latestPreEnrollmentIds()
    {
        // We use a subquery to find the highest submission_version per student,
        // then take the MAX(id) for that version to ensure a single row per student.
        return \Illuminate\Support\Facades\DB::table('pre_enrollments')
            ->select('student_id', \Illuminate\Support\Facades\DB::raw('MAX(id) as latest_id'))
            ->whereIn(\Illuminate\Support\Facades\DB::raw('(student_id, submission_version)'), function ($query) {
                $query->select('student_id', \Illuminate\Support\Facades\DB::raw('MAX(submission_version)'))
                    ->from('pre_enrollments')
                    ->groupBy('student_id');
            })
            ->groupBy('student_id');
    }
}