<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('estado', function (Blueprint $table) {
            $table->bigIncrements('idestado');
            $table->enum('estado', [
                'Agendada','En curso','Cancelada',
                'Finalizada','En Espera','Aprobado','Denegado','Activo','Inactivo'
            ]);
            $table->string('descripcion', 100)->nullable();
        });
    }

    public function down() {
        Schema::dropIfExists('estado');
    }
};
