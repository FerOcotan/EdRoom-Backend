<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        // Agrega índice único en la columna nombre para evitar duplicados a nivel DB
        Schema::table('curso', function (Blueprint $table) {
            // usar nombre corto para índice
            $table->unique('nombre', 'curso_nombre_unique');
        });
    }

    public function down() {
        Schema::table('curso', function (Blueprint $table) {
            $table->dropUnique('curso_nombre_unique');
        });
    }
};
