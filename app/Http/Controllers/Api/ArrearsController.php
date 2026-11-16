<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class ArrearsController extends Controller
{
    /** GET /api/arrears — daftar tunggakan: tagihan belum lunas yang sudah lewat jatuh tempo. */
    public function __invoke(): array
    {
        $tunggakan = self::daftarTunggakan();

        return [
            'data' => $tunggakan,
            'ringkasan' => [
                'jumlah_tagihan' => $tunggakan->count(),
                'total_tunggakan' => $tunggakan->sum('total_hari_ini'),
            ],
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public static function daftarTunggakan(): Collection
    {
        $hariIni = now()->startOfDay();

        return Invoice::query()
            ->with(['contract.tenant:id,nama,telepon', 'contract.room.property:id,nama'])
            ->where('status', Invoice::STATUS_BELUM_LUNAS)
            ->whereDate('jatuh_tempo', '<', $hariIni->toDateString())
            ->orderBy('jatuh_tempo')
            ->get()
            ->map(function (Invoice $invoice) use ($hariIni) {
                $rincian = $invoice->rincianPembayaran($hariIni);

                return [
                    'invoice_id' => $invoice->id,
                    'periode' => $invoice->periode,
                    'penyewa' => $invoice->contract->tenant->nama,
                    'telepon' => $invoice->contract->tenant->telepon,
                    'properti' => $invoice->contract->room->property->nama,
                    'kamar' => $invoice->contract->room->nomor,
                    'jatuh_tempo' => $invoice->jatuh_tempo->toDateString(),
                    'jumlah' => $invoice->jumlah,
                    'hari_telat' => $rincian['hari_telat'],
                    'persen_denda' => $rincian['persen_denda'],
                    'denda_hari_ini' => $rincian['denda'],
                    'total_hari_ini' => $rincian['total'],
                ];
            });
    }
}
