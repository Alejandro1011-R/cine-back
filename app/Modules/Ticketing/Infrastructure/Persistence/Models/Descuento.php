<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id_d
 * @property string|null $nombre_d
 * @property float|null $porciento
 */
class Descuento extends Model
{
    use SoftDeletes;

    protected $table = 'descuentos';

    protected $primaryKey = 'id_d';

    protected $fillable = [
        'nombre_d',
        'porciento',
    ];

    protected $casts = [
        'porciento' => 'float',
    ];
}
