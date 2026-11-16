<?php

namespace Tests\Unit;

use App\Services\BillingCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Aturan bisnis: tagihan bulan pertama prorata & denda keterlambatan 2%/minggu maks 10%.
 */
class BillingCalculatorTest extends TestCase
{
    private BillingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new BillingCalculator;
    }

    public function test_tagihan_bulan_pertama_dihitung_proporsional_dari_tanggal_masuk(): void
    {
        // November 30 hari, masuk tanggal 21 → menempati 10 hari → 10/30 × 1.200.000.
        $jumlah = $this->calculator->prorate(1_200_000, CarbonImmutable::parse('2026-11-21'));

        $this->assertSame(400_000, $jumlah);
    }

    public function test_masuk_tanggal_satu_membayar_harga_penuh(): void
    {
        $this->assertSame(1_200_000, $this->calculator->prorate(1_200_000, CarbonImmutable::parse('2026-11-01')));
        // Februari 2027 (28 hari), masuk tanggal 15 → 14/28 = setengah harga.
        $this->assertSame(450_000, $this->calculator->prorate(900_000, CarbonImmutable::parse('2027-02-15')));
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function kasusDenda(): array
    {
        return [
            'tepat waktu' => ['2026-11-10', 0],
            'telat 1 hari = minggu ke-1' => ['2026-11-11', 2],
            'telat 7 hari = minggu ke-1' => ['2026-11-17', 2],
            'telat 8 hari = minggu ke-2' => ['2026-11-18', 4],
            'telat 30 hari = minggu ke-5' => ['2026-12-10', 10],
            'telat 60 hari tetap maks 10%' => ['2027-01-09', 10],
        ];
    }

    #[DataProvider('kasusDenda')]
    public function test_denda_keterlambatan_dua_persen_per_minggu_maksimal_sepuluh_persen(string $tanggalBayar, int $persenDiharapkan): void
    {
        $jatuhTempo = CarbonImmutable::parse('2026-11-10');

        $persen = $this->calculator->lateFeePercent($jatuhTempo, CarbonImmutable::parse($tanggalBayar));

        $this->assertSame($persenDiharapkan, $persen);
    }

    public function test_nominal_denda_dihitung_dari_persen(): void
    {
        $this->assertSame(48_000, $this->calculator->lateFee(1_200_000, 4));
        $this->assertSame(0, $this->calculator->lateFee(1_200_000, 0));
    }
}
