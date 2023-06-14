<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('grades')->insert(['id' => 1, 'name' => '1º ESO']);
        DB::table('grades')->insert(['id' => 2, 'name' => '2º ESO']);
        DB::table('grades')->insert(['id' => 3, 'name' => '3º ESO']);
        DB::table('grades')->insert(['id' => 4, 'name' => '4º ESO']);

        DB::table('grades')->insert(['id' => 5, 'name' => '1º FPB']);
        DB::table('grades')->insert(['id' => 6, 'name' => '2º FPB']);

        DB::table('grades')->insert(['id' => 7, 'name' => '1º Bachillerato']);
        DB::table('grades')->insert(['id' => 8, 'name' => '2º Bachillerato']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('grades')->delete();
    }
};
