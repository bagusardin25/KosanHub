<?php

namespace App\Jobs;

use App\Models\InvoiceRun;
use App\Services\InvoiceGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job pembuatan tagihan bulanan. Dimasukkan ke antrean (tabel `jobs`) oleh container app,
 * lalu diproses oleh container worker (`php artisan queue:work`).
 */
class GenerateMonthlyInvoices implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $invoiceRunId) {}

    public function handle(InvoiceGenerator $generator): void
    {
        $run = InvoiceRun::findOrFail($this->invoiceRunId);
        $run->update(['status' => InvoiceRun::STATUS_PROCESSING]);

        $hasil = $generator->generateForPeriod($run->periode);

        $run->update([
            'status' => InvoiceRun::STATUS_DONE,
            'jumlah_dibuat' => $hasil['dibuat'],
            'jumlah_dilewati' => $hasil['dilewati'],
            'selesai_pada' => now(),
        ]);

        Log::info("Tagihan periode {$run->periode} selesai: {$hasil['dibuat']} dibuat, {$hasil['dilewati']} dilewati.", [
            'invoice_run_id' => $run->id,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        InvoiceRun::whereKey($this->invoiceRunId)->update([
            'status' => InvoiceRun::STATUS_FAILED,
            'pesan_error' => $exception?->getMessage(),
        ]);
    }
}
