<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_id', 'tanggal_bayar', 'jumlah_pokok', 'persen_denda', 'denda', 'total', 'metode'])]
class Payment extends Model
{
    public const METODE = ['TUNAI', 'TRANSFER', 'QRIS'];

    protected function casts(): array
    {
        return [
            'tanggal_bayar' => 'date:Y-m-d',
            'jumlah_pokok' => 'integer',
            'persen_denda' => 'integer',
            'denda' => 'integer',
            'total' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
