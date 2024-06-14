<?php

use App\Http\Controllers\Api\AcademicYearController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BookCopyController;
use App\Http\Controllers\Api\CohortController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\ObservationController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\LendingController;
use App\Http\Controllers\Api\LendingStatsController;
use App\Http\Controllers\Api\GradeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('login', [LoginController::class, 'login'])->name('login.login');

Route::middleware('verifyToken')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('login.logout');

    /*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });*/

    Route::apiResource('books', BookController::class);
    Route::get('books/{id}/copies', [BookController::class, 'getBookCopies'])
        ->where('id', '[0-9]+')
        ->name('books.copies');

    Route::apiResource('grades', GradeController::class)->only(['index']);

    Route::get('bookcopies/barcode/{barcode}', [BookCopyController::class, 'showByBarcode'])
        ->where('barcode', '[0-9]{13}')
        ->name('bookcopies.showbybarcode');
    Route::post('books/{book_id}/copies/{count}/status/{status_id}', [BookCopyController::class, 'store'])
        ->where('book_id', '[0-9]+')
        ->where('count', '[0-9]+')
        ->where('status_id', '[0-9]+')
        ->name('bookcopies.store');
    Route::put('bookcopies/{id}', [BookCopyController::class, 'update'])
        ->where('id', '[0-9]+')
        ->name('bookcopies.update');

    Route::get('statuses', [StatusController::class, 'index'])->name('statuses.index');

    Route::get('observations', [ObservationController::class, 'index'])->name('observations.index');

    Route::apiResource('cohorts', CohortController::class)->only(['index', 'show']);
    Route::post('cohorts/bulk', [CohortController::class, 'storeBulk'])->name('cohorts.storebulk');

    Route::apiResource('students', StudentController::class)->only(['index', 'show']);
    Route::post('students/bulk', [StudentController::class, 'storeBulk'])->name('students.storebulk');
    Route::post('students/messaging', [StudentController::class, 'cohortsMessaging'])
        ->name('students.messaging');

    Route::apiResource('lendings', LendingController::class)->only(['store', 'update', 'destroy']);
    Route::post('lendings/edit/{lending_id}', [LendingController::class, 'edit'])
        ->where('lending_id', '[0-9]+')
        ->name('lendings.edit');
    Route::get('lendings/student/{student_id}/index', [LendingController::class, 'indexByStudent'])
        ->where('student_id', '[0-9]+')
        ->name('lendings.indexbystudent');
    Route::get('lendings/book/barcode/{barcode}/index', [LendingController::class, 'indexByBookBarcode'])
        ->where('barcode', '[0-9]{13}')
        ->name('lendings.indexbybookbarcode');
    Route::get('lendings/book/barcode/{barcode}/show', [LendingController::class, 'showByBookBarcode'])
        ->where('barcode', '[0-9]{13}')
        ->name('lendings.showbybookbarcode');
    Route::post('lendings/messaging', [LendingController::class, 'gradesMessaging'])
        ->name('lendings.messaging');
    /*Route::post('lendings/return', [LendingController::class, 'return'])
        ->name('lending.return');*/

    Route::get('lendings/stats/list/return/cohort/{cohort_id}/academicyear/{academic_year_id}', [LendingStatsController::class, 'listStudentsReturnByCohort'])
        ->where('cohort_id', '[0-9]+')
        ->where('academic_year_id', '[0-9]+')
        ->name('lendingsstats.liststudentsreturn');

    Route::apiResource('academicyears', AcademicYearController::class)->only(['index', 'show', 'store']);
});
