<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('clase', function (Blueprint $table) {
            $table->bigIncrements('idclase');

            $table->unsignedBigInteger('idcurso');
            $table->string('tema', 100);
       $table->timestamp('fechahorainicio')->nullable();
$table->timestamp('fechahorafinal')->nullable();

            $table->string('url', 100)->nullable();

            $table->unsignedBigInteger('idestado');

            // FKs
            $table->foreign('idcurso')->references('idcurso')->on('curso')
                  ->onDelete('cascade');

            $table->foreign('idestado')->references('idestado')->on('estado')
                  ->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('clase');
    }
};
