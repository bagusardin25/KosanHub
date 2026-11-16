<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Mencatat pelunasan tagihan. Denda dan total dihitung di server, bukan dikirim klien.
     */
    public function pay(Invoice $invoice, CarbonInterface $tanggalBayar, string $metode): Payment
    {
        return DB::transaction(function () use ($invoice, $tanggalBayar, $metode) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->status === Invoice::STATUS_LUNAS) {
                throw ValidationException::withMessages([
                    'invoice' => 'Tagihan ini sudah lunas.',
                ]);
            }

            if ($tanggalBayar->lt($invoice->tanggal_terbit)) {
                throw ValidationException::withMessages([
                    'tanggal_bayar' => 'Tanggal bayar tidak boleh sebelum tanggal terbit tagihan.',
                ]);
            }

            $rincian = $invoice->rincianPembayaran($tanggalBayar);

            $payment = $invoice->payment()->create([
                'tanggal_bayar' => $tanggalBayar,
                'jumlah_pokok' => $invoice->jumlah,
                'persen_denda' => $rincian['persen_denda'],
                'denda' => $rincian['denda'],
                'total' => $rincian['total'],
                'metode' => $metode,
            ]);

            $invoice->update(['status' => Invoice::STATUS_LUNAS]);

            return $payment;
        });
    }
}
