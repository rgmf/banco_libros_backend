<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lendings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('book_copy_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('academic_year_id')
                  ->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->timestamp('lending_date');
            $table->timestamp('returned_date')->nullable();

            $table->unsignedBigInteger('lending_status_id');
            $table->foreign('lending_status_id')
                  ->references('id')->on('statuses')->cascadeOnUpdate()->noActionOnDelete();

            $table->unsignedBigInteger('returned_status_id')->nullable();
            $table->foreign('returned_status_id')
                  ->references('id')->on('statuses')->cascadeOnUpdate()->noActionOnDelete();
            $table->unique(['student_id', 'book_copy_id', 'academic_year_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lendings');
    }
};
