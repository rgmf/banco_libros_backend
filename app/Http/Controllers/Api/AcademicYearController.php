<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYearRequest;
use App\Http\Resources\AcademicYearCollection;
use App\Http\Resources\AcademicYearResource;
use App\Http\Resources\ErrorResource;
use App\Models\AcademicYear;
use Illuminate\Database\QueryException;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::get();
        return new AcademicYearCollection($academicYears);
    }

    public function show(int $id)
    {
        $academicYear = AcademicYear::find($id);
        if (!$academicYear) {
            return new ErrorResource(404, 'El año académico que solicitas no existe');
        }
        return new AcademicYearResource($academicYear);
    }

    public function store(AcademicYearRequest $request)
    {
        try {
            $academicYear = new AcademicYear();
            $academicYear->name = $request->input('name');
            $academicYear->save();
        } catch (QueryException $exception) {
            $error_code = $exception->errorInfo[1];
            if ($error_code == 1062) {
                return new ErrorResource(409, 'El curso académico ya existe', $exception);
            } else {
                return new ErrorResource(500, 'Error al insertar el curso académico', $exception);
            }
        }

        return new AcademicYearResource($academicYear, 201);
    }
}
