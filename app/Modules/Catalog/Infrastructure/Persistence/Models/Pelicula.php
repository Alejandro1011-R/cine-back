<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Models;

use App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id_p
 * @property string|null $sinopsis
 * @property int|null $anno
 * @property int|null $nacionalidad
 * @property int|null $duracion
 * @property string|null $titulo
 * @property string|null $imagen
 * @property string|null $trailer
 */
class Pelicula extends Model
{
    use SoftDeletes;

    protected $table = 'peliculas';

    protected $primaryKey = 'id_p';

    protected $fillable = [
        'sinopsis',
        'anno',
        'nacionalidad',
        'duracion',
        'titulo',
        'imagen',
        'trailer',
    ];

    protected $casts = [
        'anno' => 'integer',
        'nacionalidad' => 'integer',
        'duracion' => 'integer',
    ];

    public function actores(): BelongsToMany
    {
        return $this->belongsToMany(
            Actor::class,
            'elenco',
            'id_p',
            'id_a',
        );
    }

    public function generos(): BelongsToMany
    {
        return $this->belongsToMany(
            Genero::class,
            'pelicula_genero',
            'id_p',
            'id_g',
        );
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'id_p', 'id_p');
    }
}
