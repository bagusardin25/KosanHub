<?php

namespace App\Services;

use Carbon\CarbonInterface;

/**
 * Semua rumus uang KosanHub dikumpulkan di sini (tanpa akses database)
 * supaya mudah diuji dengan unit test.
 */
class BillingCalculator
{
    public const DENDA_PER_MINGGU_PERSEN = 2;

    public const DENDA_MAKSIMAL_PERSEN = 10;

    /** Jumlah hari dari tanggal terbit sampai jatuh tempo. */
    public const HARI_JATUH_TEMPO = 7;

    /**
     * Tagihan bulan pertama dihitung proporsional:
     * harga bulanan × (hari ditempati di bulan masuk, termasuk tanggal masuk) / jumlah hari di bulan itu.
     *
     * Contoh: Rp1.200.000, masuk 21 November (30 hari) → 10/30 × 1.200.000 = Rp400.000.
     */
    public function prorate(int $hargaBulanan, CarbonInterface $tanggalMasuk): int
    {
        $hariDalamBulan = $tanggalMasuk->daysInMonth;
        $hariDitempati = $hariDalamBulan - $tanggalMasuk->day + 1;

        return (int) round($hargaBulanan * $hariDitempati / $hariDalamBulan);
    }

    /** Jumlah hari keterlambatan (0 jika dibayar sebelum/pada jatuh tempo). */
    public function daysLate(CarbonInterface $jatuhTempo, CarbonInterface $tanggalBayar): int
    {
        $selisih = (int) $jatuhTempo->toImmutable()->startOfDay()
            ->diffInDays($tanggalBayar->toImmutable()->startOfDay(), false);

        return max(0, $selisih);
    }

    /**
     * Denda 2% per minggu keterlambatan, maksimal 10%.
     * Asumsi tim: minggu yang baru berjalan sebagian tetap dihitung satu minggu (pembulatan ke atas),
     * jadi telat 1–7 hari = 2%, 8–14 hari = 4%, dan seterusnya.
     */
    public function lateFeePercent(CarbonInterface $jatuhTempo, CarbonInterface $tanggalBayar): int
    {
        $hariTelat = $this->daysLate($jatuhTempo, $tanggalBayar);

        if ($hariTelat === 0) {
            return 0;
        }

        $minggu = (int) ceil($hariTelat / 7);

        return min($minggu * self::DENDA_PER_MINGGU_PERSEN, self::DENDA_MAKSIMAL_PERSEN);
    }

    public function lateFee(int $jumlah, int $persen): int
    {
        return (int) round($jumlah * $persen / 100);
    }
}
