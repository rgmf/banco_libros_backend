<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkCohortsRequest;
use App\Http\Resources\CohortCollection;
use App\Http\Resources\ErrorResource;
use App\Models\Cohort;
use Illuminate\Database\QueryException;

class CohortController extends Controller
{
    public function storeBulk(BulkCohortsRequest $request)
    {
        try {
            $cohorts = $request->input('cohorts');
            Cohort::insert($cohorts);
        } catch (QueryException $exception) {
            $error_code = $exception->errorInfo[1];
            if ($error_code == 1062) {
                return new ErrorResource(409, 'Hay grupos que ya existen', $exception);
            } else {
                return new ErrorResource(500, 'Error al insertar los grupos', $exception);
            }
        }

        return new CohortCollection($cohorts);
    }
}
