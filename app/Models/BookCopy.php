<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookCopy extends Model
{
    use HasFactory;

    protected $fillable = [
        'barcode',
        'comment',
        'book_id',
        'status_id'
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function observations(): BelongsToMany
    {
        return $this->belongsToMany(Observation::class);
    }

    public function lendings(): HasMany
    {
        return $this->hasMany(Lending::class);
    }
}
