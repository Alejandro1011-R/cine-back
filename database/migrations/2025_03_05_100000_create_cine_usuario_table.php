<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cine_usuario', function (Blueprint $table): void {
            $table->unsignedInteger('id_c');
            $table->char('ci', 11);
            $table->timestamps();

            $table->primary(['id_c', 'ci']);
            $table->foreign('id_c')->references('id_c')->on('cines')->cascadeOnDelete();
            $table->foreign('ci')->references('ci')->on('usuarios')->cascadeOnDelete();
        });

        DB::table('usuarios')
            ->whereNotNull('id_c')
            ->orderBy('ci')
            ->select(['ci', 'id_c'])
            ->chunk(100, function ($usuarios): void {
                foreach ($usuarios as $usuario) {
                    DB::table('cine_usuario')->updateOrInsert(
                        ['ci' => $usuario->ci, 'id_c' => $usuario->id_c],
                        ['created_at' => now(), 'updated_at' => now()],
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('cine_usuario');
    }
};
