<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes; 
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne; // Added for clarity

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'extension_name',
        'birthday',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
            'birthday' => 'date',
        ];
    }

    /**
     * The primary student relationship used by performSync.
     * This ensures $user->student works correctly in your tiered validation.
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Helper to get the specific profile for the current active year.
     * Useful for your SerbIsko dashboard views.
     */
    public function activeStudent(): HasOne
    {
        return $this->hasOne(Student::class)->where('school_year', Student::activeYear());
    }
    
    /**
     * Keeps track of all historical enrollments (Grade 11, Grade 12, etc.)
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}