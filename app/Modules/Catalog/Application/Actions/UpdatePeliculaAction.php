<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\DTOs\PeliculaData;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Pelicula;
use Illuminate\Support\Facades\DB;

final class UpdatePeliculaAction
{
    public function handle(int $id, PeliculaData $data): ?Pelicula
    {
        return DB::transaction(function () use ($id, $data): ?Pelicula {
            $pelicula = Pelicula::find($id);

            if ($pelicula === null) {
                return null;
            }

            $pelicula->update([
                'sinopsis' => $data->sinopsis,
                'anno' => $data->anno,
                'nacionalidad' => $data->nacionalidad,
                'duracion' => $data->duracion,
                'titulo' => $data->titulo,
                'imagen' => $data->imagen,
                'trailer' => $data->trailer,
            ]);

            $pelicula->actores()->sync($data->actorIds);
            $pelicula->generos()->sync($data->generoIds);

            $pelicula->load(['actores', 'generos']);

            return $pelicula;
        });
    }
}
