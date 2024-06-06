<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lending extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'book_copy_id',
        'academic_year_id',
        'lending_date',
        'returned_date',
        'lending_status_id',
        'returned_status_id',
        'lending_comment'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
