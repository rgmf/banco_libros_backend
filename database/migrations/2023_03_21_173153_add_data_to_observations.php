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
        DB::table('observations')->insert(['id' => 1, 'title' => 'Bordes estropeados']);
        DB::table('observations')->insert(['id' => 2, 'title' => 'Subrayado a bolígrafo y/o fluorescente']);
        DB::table('observations')->insert(['id' => 3, 'title' => 'Marcado a lápiz']);
        DB::table('observations')->insert(['id' => 4, 'title' => 'Hoja rota']);
        DB::table('observations')->insert(['id' => 5, 'title' => 'Tapas estropeadas']);
        DB::table('observations')->insert(['id' => 6, 'title' => 'Anotaciones a lápiz y/o bolígrafo']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('observations')->delete();
    }
};
