<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Agregar idrol debajo del campo id
            $table->unsignedBigInteger('idrol')->after('id');

            // Agregar idestado debajo de idrol
            $table->unsignedBigInteger('idestado')->after('idrol');

            // Llave foránea a la tabla rol
            $table->foreign('idrol')
                ->references('idrol')
                ->on('rol')
                ->onDelete('cascade');

            // Llave foránea a la tabla estado
            $table->foreign('idestado')
                ->references('idestado')
                ->on('estado')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Primero eliminar foreign keys
            $table->dropForeign(['idrol']);
            $table->dropForeign(['idestado']);

            // Luego eliminar columnas
            $table->dropColumn('idrol');
            $table->dropColumn('idestado');
        });
    }
};
