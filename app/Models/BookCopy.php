<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookCopy extends Model
{
    use HasFactory;

    protected $fillable = [
        'barcode',
        'comment',
        'book_id',
        'status_id'
    ];

}
