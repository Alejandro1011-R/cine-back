<?php

namespace Database\Seeders;

use App\Modules\Catalog\Infrastructure\Persistence\Models\Genero;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Pelicula;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Butaca;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Cine;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sala;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Descuento;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createUsuario('99999999999', UserRole::SUPER_ADMIN->value, 'Root Admin', 'root@example.com');
        $admin = $this->createUsuario('88888888888', UserRole::ADMIN->value, 'Admin Centro', 'admin.centro@example.com');
        $taquillero = $this->createUsuario('77777777777', UserRole::TAQUILLERO->value, 'Taquilla Centro', 'taquilla.centro@example.com');
        $this->createUsuario('11111111111', UserRole::CLIENTE->value, 'Cliente Demo', 'cliente@example.com');
        $this->createUsuario('22222222222', UserRole::SOCIO->value, 'Socio Demo', 'socio@example.com', 120);

        $cineCentro = Cine::firstOrCreate(
            ['nombre' => 'Cine Centro'],
            ['direccion' => 'Avenida Principal 100'],
        );
        $cineNorte = Cine::firstOrCreate(
            ['nombre' => 'Cine Norte'],
            ['direccion' => 'Calle Norte 25'],
        );

        $admin->cines()->syncWithoutDetaching([$cineCentro->id_c, $cineNorte->id_c]);
        $taquillero->cines()->syncWithoutDetaching([$cineCentro->id_c]);

        $genero = Genero::firstOrCreate(['nombre_g' => 'Drama']);
        $pelicula = Pelicula::firstOrCreate(
            ['titulo' => 'Reserva Final'],
            [
                'sinopsis' => 'Demo de reservas, pagos y auditoría.',
                'anno' => 2025,
                'nacionalidad' => 34,
                'duracion' => 120,
            ],
        );
        $pelicula->generos()->syncWithoutDetaching([$genero->id_g]);

        $salaCentro = Sala::firstOrCreate(
            ['id_c' => $cineCentro->id_c, 'capacidad' => 50],
            ['capacidad' => 50],
        );

        $existingSeats = Butaca::where('id_s', $salaCentro->id_s)->count();
        for ($i = $existingSeats; $i < 10; $i++) {
            Butaca::create(['id_s' => $salaCentro->id_s]);
        }

        $sesion = new Sesion();
        $sesion->id_p = $pelicula->id_p;
        $sesion->id_s = $salaCentro->id_s;
        $sesion->fecha = '2026-06-15 20:00:00';
        if (!Sesion::where('id_p', $sesion->id_p)->where('id_s', $sesion->id_s)->where('fecha', $sesion->fecha)->exists()) {
            $sesion->save();
        }

        Descuento::firstOrCreate(
            ['nombre_d' => 'Demo 10'],
            ['porciento' => 10],
        );
    }

    private function createUsuario(string $ci, string $rol, string $nombre, string $correo, int $puntos = 0): Usuario
    {
        Cliente::firstOrCreate(
            ['ci' => $ci],
            ['correo' => $correo, 'confiabilidad' => true],
        );

        return Usuario::firstOrCreate(
            ['ci' => $ci],
            [
                'nombre_s' => $nombre,
                'apellidos' => 'Demo',
                'puntos' => $puntos,
                'codigo' => $ci,
                'contrasena' => Hash::make('password123'),
                'rol' => $rol,
            ],
        );
    }
}
