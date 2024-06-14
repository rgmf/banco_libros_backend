<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkCohortsRequest;
use App\Http\Resources\CohortCollection;
use App\Http\Resources\CohortResource;
use App\Models\Cohort;
use Illuminate\Database\QueryException;

class CohortController extends Controller
{
    public function index()
    {
        $cohorts = Cohort::orderBy('name')->get();
        return new CohortCollection($cohorts);
    }

    public function show(int $id)
    {
        $cohort = Cohort::find($id);
        if (!$cohort) {
            return new ErrorResource(404, 'El curso que solicitas no existe');
        }
        return new CohortResource($cohort);
    }

    public function storeBulk(BulkCohortsRequest $request)
    {
        $cohorts = $request->input('cohorts');

        $cohortsInserted = collect($cohorts)->filter(function($cohort) {
            try {
                Cohort::insert($cohort);
                return true;
            } catch (QueryException $exception) {
                return false;
            }
        });

        return new CohortCollection($cohortsInserted);
    }
}
