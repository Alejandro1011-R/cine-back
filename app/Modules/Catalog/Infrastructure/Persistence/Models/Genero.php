<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id_g
 * @property string|null $nombre_g
 */
class Genero extends Model
{
    use SoftDeletes;

    protected $table = 'generos';

    protected $primaryKey = 'id_g';

    protected $fillable = [
        'nombre_g',
    ];

    public function peliculas(): BelongsToMany
    {
        return $this->belongsToMany(
            Pelicula::class,
            'pelicula_genero',
            'id_g',
            'id_p',
        );
    }
}
