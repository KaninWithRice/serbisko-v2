<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = [
        'academic_year',
        'grade_level',
        'name',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
