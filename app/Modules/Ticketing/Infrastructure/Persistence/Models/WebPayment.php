<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id_pg
 * @property string|null $codigo_t
 * @property float|null $cantidad
 * @property string|null $gateway_reference
 * @property string|null $gateway_status
 * @property string|null $card_brand
 * @property string|null $card_last_four
 */
class WebPayment extends Model
{
    use SoftDeletes;

    protected $table = 'web_payments';

    protected $primaryKey = 'id_pg';

    public $incrementing = false;

    protected $fillable = [
        'id_pg',
        'codigo_t',
        'cantidad',
        'gateway_reference',
        'gateway_status',
        'card_brand',
        'card_last_four',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pg', 'id_pg');
    }
}
