<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StatusCollection;
use App\Models\Status;

class StatusController extends Controller
{
    public function index()
    {
        $statuses = Status::get();
        return new StatusCollection($statuses);
    }
}
