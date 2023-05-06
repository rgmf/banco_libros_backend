<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ObservationCollection;
use App\Models\Observation;

class ObservationController extends Controller
{
    public function index()
    {
        $observations = Observation::get();
        return new ObservationCollection($observations);
    }
}
