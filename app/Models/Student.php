<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nia',
        'name',
        'lastname1',
        'lastname2',
        'cohort_id',
        'picture',
        'nationality',
        'address',
        'city',
        'cp',
        'phone1',
        'phone2',
        'phone3',
        'name_father',
        'lastname1_father',
        'lastname2_father',
        'email_father',
        'name_mother',
        'lastname1_mother',
        'lastname2_mother',
        'email_mother',
        'is_member'
    ];

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function lendings(): HasMany
    {
        return $this->hasMany(Lending::class);
    }
}
