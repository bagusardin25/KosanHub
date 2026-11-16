<?php

namespace Tests\Feature;

use App\Jobs\GenerateMonthlyInvoices;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceRun;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\ContractService;
use App\Services\InvoiceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Aturan bisnis: tagihan bulanan tidak boleh terbit dua kali untuk periode yang sama,
 * dan tagihan bulan pertama dihitung proporsional.
 */
class InvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function buatKontrak(string $tanggalMulai, int $harga = 1_200_000): Contract
    {
        return app(ContractService::class)->create([
            'room_id' => Room::factory()->create(['harga_bulanan' => $harga])->id,
            'tenant_id' => Tenant::factory()->create()->id,
            'tanggal_mulai' => $tanggalMulai,
        ]);
    }

    public function test_tagihan_tidak_terbit_dua_kali_untuk_periode_yang_sama(): void
    {
        $this->buatKontrak('2026-10-01');
        $this->buatKontrak('2026-10-01');
        $generator = app(InvoiceGenerator::class);

        $pertama = $generator->generateForPeriod('2026-11');
        $kedua = $generator->generateForPeriod('2026-11');

        $this->assertSame(['dibuat' => 2, 'dilewati' => 0], $pertama);
        $this->assertSame(['dibuat' => 0, 'dilewati' => 2], $kedua);
        $this->assertSame(2, Invoice::where('periode', '2026-11')->count());
    }

    public function test_tagihan_bulan_pertama_prorata_dan_bulan_berikutnya_penuh(): void
    {
        $kontrak = $this->buatKontrak('2026-11-21');
        $generator = app(InvoiceGenerator::class);

        $generator->generateForPeriod('2026-11');
        $generator->generateForPeriod('2026-12');

        $november = Invoice::where('contract_id', $kontrak->id)->where('periode', '2026-11')->firstOrFail();
        $desember = Invoice::where('contract_id', $kontrak->id)->where('periode', '2026-12')->firstOrFail();

        $this->assertSame(400_000, $november->jumlah);
        $this->assertTrue($november->prorata);
        $this->assertSame('2026-11-28', $november->jatuh_tempo->toDateString());
        $this->assertSame(1_200_000, $desember->jumlah);
        $this->assertFalse($desember->prorata);
    }

    public function test_kontrak_yang_belum_mulai_tidak_ditagih(): void
    {
        $this->buatKontrak('2027-01-05');

        $hasil = app(InvoiceGenerator::class)->generateForPeriod('2026-12');

        $this->assertSame(0, $hasil['dibuat']);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_api_memasukkan_job_tagihan_ke_antrean_untuk_worker(): void
    {
        Queue::fake();

        $run = $this->postJson('/api/invoice-runs', ['periode' => '2026-11'])
            ->assertAccepted()
            ->assertJsonPath('data.status', InvoiceRun::STATUS_PENDING)
            ->json('data');

        Queue::assertPushed(GenerateMonthlyInvoices::class, fn ($job) => $job->invoiceRunId === $run['id']);
    }

    public function test_job_tagihan_yang_diproses_worker_mencatat_hasilnya(): void
    {
        // phpunit.xml memakai QUEUE_CONNECTION=sync, jadi job langsung diproses di test ini.
        $this->buatKontrak('2026-10-01');

        $run = $this->postJson('/api/invoice-runs', ['periode' => '2026-11'])->json('data');

        $this->getJson("/api/invoice-runs/{$run['id']}")
            ->assertOk()
            ->assertJsonPath('data.status', InvoiceRun::STATUS_DONE)
            ->assertJsonPath('data.jumlah_dibuat', 1);
    }

    public function test_format_periode_divalidasi(): void
    {
        $this->postJson('/api/invoice-runs', ['periode' => '2026-13'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('periode');
    }
}
