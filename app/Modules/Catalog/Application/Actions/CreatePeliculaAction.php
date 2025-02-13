<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\DTOs\PeliculaData;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Pelicula;
use Illuminate\Support\Facades\DB;

final class CreatePeliculaAction
{
    public function handle(PeliculaData $data): Pelicula
    {
        return DB::transaction(function () use ($data): Pelicula {
            $pelicula = Pelicula::create([
                'sinopsis' => $data->sinopsis,
                'anno' => $data->anno,
                'nacionalidad' => $data->nacionalidad,
                'duracion' => $data->duracion,
                'titulo' => $data->titulo,
                'imagen' => $data->imagen,
                'trailer' => $data->trailer,
            ]);

            if (!empty($data->actorIds)) {
                $pelicula->actores()->attach($data->actorIds);
            }

            if (!empty($data->generoIds)) {
                $pelicula->generos()->attach($data->generoIds);
            }

            $pelicula->load(['actores', 'generos']);

            return $pelicula;
        });
    }
}
