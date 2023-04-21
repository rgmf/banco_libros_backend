<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BookCopyController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\StudentController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('books', BookController::class);
Route::get('books/{id}/copies', [BookController::class, 'getBookCopies'])
    ->where('id', '[0-9]+')
    ->name('books.copies');

Route::post('books/{book_id}/copies/{count}/status/{status_id}', [BookCopyController::class, 'store'])
    ->where('book_id', '[0-9]+')
    ->where('count', '[0-9]+')
    ->where('status_id', '[0-9]+')
    ->name('bookcopies.store');

Route::get('statuses', [StatusController::class, 'index'])->name('statuses.index');

Route::apiResource('students', StudentController::class)->only(['index']);
Route::post('students/bulk', [StudentController::class, 'storeBulk'])->name('students.storebulk');
