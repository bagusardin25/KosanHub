<?php

namespace App\Models;

use App\Services\BillingCalculator;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['contract_id', 'periode', 'tanggal_terbit', 'jatuh_tempo', 'jumlah', 'prorata', 'status'])]
class Invoice extends Model
{
    public const STATUS_BELUM_LUNAS = 'BELUM_LUNAS';

    public const STATUS_LUNAS = 'LUNAS';

    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date:Y-m-d',
            'jatuh_tempo' => 'date:Y-m-d',
            'jumlah' => 'integer',
            'prorata' => 'boolean',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Rincian denda jika tagihan ini dibayar pada tanggal tertentu.
     *
     * @return array{hari_telat: int, persen_denda: int, denda: int, total: int}
     */
    public function rincianPembayaran(CarbonInterface $tanggalBayar): array
    {
        $calculator = app(BillingCalculator::class);
        $persen = $calculator->lateFeePercent($this->jatuh_tempo, $tanggalBayar);
        $denda = $calculator->lateFee($this->jumlah, $persen);

        return [
            'hari_telat' => $calculator->daysLate($this->jatuh_tempo, $tanggalBayar),
            'persen_denda' => $persen,
            'denda' => $denda,
            'total' => $this->jumlah + $denda,
        ];
    }
}
