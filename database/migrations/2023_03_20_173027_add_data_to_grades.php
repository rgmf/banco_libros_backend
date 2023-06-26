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

        DB::table('grades')->insert(['id' => 20, 'name' => '1º FPB Peluquería']);
        DB::table('grades')->insert(['id' => 21, 'name' => '2º FPB Peluquería']);
        DB::table('grades')->insert(['id' => 22, 'name' => '1º FPB Estética']);
        DB::table('grades')->insert(['id' => 23, 'name' => '2º FPB Estética']);
        DB::table('grades')->insert(['id' => 24, 'name' => '1º FPB Informática']);
        DB::table('grades')->insert(['id' => 25, 'name' => '2º FPB Informática']);
        DB::table('grades')->insert(['id' => 26, 'name' => '1º FPB Electricidad']);
        DB::table('grades')->insert(['id' => 27, 'name' => '2º FPB Electricidad']);

        DB::table('grades')->insert(['id' => 30, 'name' => '1º Bach Ciencias']);
        DB::table('grades')->insert(['id' => 31, 'name' => '2º Bach Ciencias']);
        DB::table('grades')->insert(['id' => 32, 'name' => '1º Bach Humanidades']);
        DB::table('grades')->insert(['id' => 33, 'name' => '2º Bach Humanidades']);
        DB::table('grades')->insert(['id' => 34, 'name' => '1º Bach CCSS']);
        DB::table('grades')->insert(['id' => 35, 'name' => '2º Bach CCSS']);
        DB::table('grades')->insert(['id' => 36, 'name' => '1º Bach General']);
        DB::table('grades')->insert(['id' => 37, 'name' => '2º Bach General']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('grades')->delete();
    }
};
