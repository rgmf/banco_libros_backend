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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nia')->unique();
            $table->string('name');
            $table->string('lastname1');
            $table->string('lastname2')->nullable();
            $table->foreignId('cohort_id')
                  ->nullable()
                  ->constrained()
                  ->cascadeOnUpdate()
                  ->nullOnDelete();
            $table->string('picture')->nullable();
            $table->string('nationality')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('cp')->nullable();
            $table->string('phone1')->nullable();
            $table->string('phone2')->nullable();
            $table->string('phone3')->nullable();
            $table->string('name_father')->nullable();
            $table->string('lastname1_father')->nullable();
            $table->string('lastname2_father')->nullable();
            $table->string('email_father')->nullable();
            $table->string('name_mother')->nullable();
            $table->string('lastname1_mother')->nullable();
            $table->string('lastname2_mother')->nullable();
            $table->string('email_mother')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
