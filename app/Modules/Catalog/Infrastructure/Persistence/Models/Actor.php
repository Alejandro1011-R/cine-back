<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id_a
 * @property string|null $nombre_a
 */
class Actor extends Model
{
    use SoftDeletes;

    protected $table = 'actores';

    protected $primaryKey = 'id_a';

    protected $fillable = [
        'nombre_a',
    ];

    public function peliculas(): BelongsToMany
    {
        return $this->belongsToMany(
            Pelicula::class,
            'elenco',
            'id_a',
            'id_p',
        );
    }
}
