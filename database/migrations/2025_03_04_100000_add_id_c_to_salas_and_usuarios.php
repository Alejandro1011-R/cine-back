<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sala pertenece a un Cine (B2B: cada cine tiene sus salas)
        Schema::table('salas', function (Blueprint $table) {
            $table->unsignedInteger('id_c')->nullable()->after('id_s');
            $table->foreign('id_c')->references('id_c')->on('cines')->onDelete('set null');
        });

        // Usuario Admin/Taquillero pertenece a un Cine
        // Cliente/Socio tendrán id_c = null
        Schema::table('usuarios', function (Blueprint $table) {
            $table->unsignedInteger('id_c')->nullable()->after('ci');
            $table->foreign('id_c')->references('id_c')->on('cines')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('salas', function (Blueprint $table) {
            $table->dropForeign(['id_c']);
            $table->dropColumn('id_c');
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['id_c']);
            $table->dropColumn('id_c');
        });
    }
};
