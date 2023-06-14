<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GradeCollection;
use App\Models\Grade;

class GradeController extends Controller
{
    public function index()
    {
        $grades= Grade::get();
        return new GradeCollection($grades);
    }
}
