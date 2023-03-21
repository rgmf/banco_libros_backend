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
        DB::table('statuses')->insert(['id' => 1, 'name' => 'Nuevo']);
        DB::table('statuses')->insert(['id' => 2, 'name' => 'Bien']);
        DB::table('statuses')->insert(['id' => 3, 'name' => 'Aceptable']);
        DB::table('statuses')->insert(['id' => 4, 'name' => 'Baja']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('statuses')->delete();
    }
};
