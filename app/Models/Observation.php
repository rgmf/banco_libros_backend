<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Observation extends Model
{
    use HasFactory;

    public function bookCopies(): BelongsToMany
    {
        return $this->belongsToMany(BookCopy::class, 'book_copy_observation', 'observation_id', 'book_copy_id');
    }
}
