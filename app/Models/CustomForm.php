<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'created_by',
        'title',
        'description',
        'school_year',
        'schema',
        'firestore_doc_id',
        'share_token'
    ];

    /**
     * Clear cache on change to ensure active school year propagates immediately.
     */
    protected static function booted()
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('active_school_year'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('active_school_year'));
    }

    protected $casts = [

        'schema' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}