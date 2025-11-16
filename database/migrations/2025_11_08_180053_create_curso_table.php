<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('curso', function (Blueprint $table) {
            $table->bigIncrements('idcurso');
            $table->unsignedBigInteger('idusuario');

            $table->string('nombre', 50);
            $table->string('descripcion', 300)->nullable();
            $table->integer('maximoest');
            $table->integer('estuinscritos')->default(0);
            $table->unsignedBigInteger('idestado');

            // FK a users
            $table->foreign('idusuario')->references('id')->on('users')
                  ->onDelete('cascade');
            // FK a estado
              $table->foreign('idestado')->references('idestado')->on('estado')
                  ->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('curso');
    }
};
