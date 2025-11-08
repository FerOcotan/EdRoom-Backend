<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('soliestudiante', function (Blueprint $table) {
            $table->bigIncrements('idsoliestudiante');

            $table->unsignedBigInteger('idestudiante');
            $table->unsignedBigInteger('idcurso');
            $table->timestamp('fecha')->nullable();

            $table->unsignedBigInteger('idestado');

            // FKs
            $table->foreign('idestudiante')->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('idcurso')->references('idcurso')->on('curso')
                  ->onDelete('cascade');

            $table->foreign('idestado')->references('idestado')->on('estado')
                  ->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('soliestudiante');
    }
};
