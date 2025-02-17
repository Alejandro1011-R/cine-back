<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Infrastructure\Persistence\Models;

use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id_b
 * @property int $id_s
 */
class Butaca extends Model
{
    use SoftDeletes;

    protected $table = 'butacas';

    protected $primaryKey = 'id_b';

    protected $fillable = [
        'id_s',
    ];

    protected $casts = [
        'id_s' => 'integer',
    ];

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'id_s', 'id_s');
    }

    public function compras(): BelongsToMany
    {
        return $this->belongsToMany(
            Compra::class,
            'butacas_reservadas',
            'id_b',
            'id_p',
        );
    }
}
