<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\ContractService;
use App\Services\InvoiceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aturan bisnis: denda keterlambatan dihitung server saat pembayaran,
 * dan tagihan lewat jatuh tempo muncul di daftar tunggakan.
 */
class PaymentAndArrearsTest extends TestCase
{
    use RefreshDatabase;

    /** Membuat satu kontrak (mulai 1 Okt) beserta tagihan November-nya (jatuh tempo 8 Nov). */
    private function tagihanNovember(): Invoice
    {
        $kontrak = app(ContractService::class)->create([
            'room_id' => Room::factory()->create(['harga_bulanan' => 1_000_000])->id,
            'tenant_id' => Tenant::factory()->create()->id,
            'tanggal_mulai' => '2026-10-01',
        ]);

        app(InvoiceGenerator::class)->generateForPeriod('2026-11');

        return Invoice::where('contract_id', $kontrak->id)->where('periode', '2026-11')->firstOrFail();
    }

    public function test_pembayaran_terlambat_dikenai_denda_yang_dihitung_server(): void
    {
        $invoice = $this->tagihanNovember();

        // Jatuh tempo 8 Nov, dibayar 18 Nov → telat 10 hari → 2 minggu → 4%.
        $this->postJson("/api/invoices/{$invoice->id}/payments", [
            'tanggal_bayar' => '2026-11-18',
            'metode' => 'TRANSFER',
            'total' => 1, // nilai dari klien diabaikan
        ])
            ->assertCreated()
            ->assertJsonPath('data.persen_denda', 4)
            ->assertJsonPath('data.denda', 40_000)
            ->assertJsonPath('data.total', 1_040_000);

        $this->assertSame(Invoice::STATUS_LUNAS, $invoice->fresh()->status);
    }

    public function test_tagihan_yang_sudah_lunas_tidak_bisa_dibayar_lagi(): void
    {
        $invoice = $this->tagihanNovember();
        $payload = ['tanggal_bayar' => '2026-11-05', 'metode' => 'TUNAI'];

        $this->postJson("/api/invoices/{$invoice->id}/payments", $payload)
            ->assertCreated()
            ->assertJsonPath('data.denda', 0);

        $this->postJson("/api/invoices/{$invoice->id}/payments", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('invoice');

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_daftar_tunggakan_hanya_berisi_tagihan_belum_lunas_yang_lewat_jatuh_tempo(): void
    {
        $this->travelTo('2026-11-20 10:00:00');
        $menunggak = $this->tagihanNovember();
        $lunas = $this->tagihanNovember();
        $this->postJson("/api/invoices/{$lunas->id}/payments", ['tanggal_bayar' => '2026-11-03', 'metode' => 'QRIS']);

        $this->getJson('/api/arrears')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.invoice_id', $menunggak->id)
            ->assertJsonPath('data.0.hari_telat', 12)
            ->assertJsonPath('data.0.persen_denda', 4)
            ->assertJsonPath('ringkasan.total_tunggakan', 1_040_000);
    }
}
