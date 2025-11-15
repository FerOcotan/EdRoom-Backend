<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('session_tokens', function (Blueprint $table) {
            // longitud 64 si usas hash('sha256', ...)
            $table->string('token_hash', 64)
                  ->after('id')   // ajusta según tus columnas
                  ->index();
        });
    }

    public function down(): void
    {
        Schema::table('session_tokens', function (Blueprint $table) {
            $table->dropColumn('token_hash');
        });
    }
};
