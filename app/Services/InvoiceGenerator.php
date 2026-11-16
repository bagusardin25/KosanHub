<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

class InvoiceGenerator
{
    public function __construct(private readonly BillingCalculator $calculator) {}

    /**
     * Membuat tagihan bulanan untuk semua kontrak aktif pada periode tertentu (format YYYY-MM).
     * Aman dijalankan berulang kali: tagihan yang sudah ada untuk periode itu dilewati.
     *
     * @return array{dibuat: int, dilewati: int}
     */
    public function generateForPeriod(string $periode): array
    {
        $awalPeriode = CarbonImmutable::createFromFormat('!Y-m', $periode)->startOfMonth();
        $akhirPeriode = $awalPeriode->endOfMonth();

        $dibuat = 0;
        $dilewati = 0;

        $contracts = Contract::query()
            ->where('status', Contract::STATUS_AKTIF)
            ->whereDate('tanggal_mulai', '<=', $akhirPeriode->toDateString())
            ->lazyById();

        foreach ($contracts as $contract) {
            $this->createInvoice($contract, $periode, $awalPeriode) ? $dibuat++ : $dilewati++;
        }

        return ['dibuat' => $dibuat, 'dilewati' => $dilewati];
    }

    /**
     * Aturan bisnis: tagihan tidak boleh terbit dua kali untuk periode yang sama.
     * Mengembalikan null jika tagihan periode itu sudah ada.
     */
    public function createInvoice(Contract $contract, string $periode, CarbonImmutable $awalPeriode): ?Invoice
    {
        $sudahAda = Invoice::query()
            ->where('contract_id', $contract->id)
            ->where('periode', $periode)
            ->exists();

        if ($sudahAda) {
            return null;
        }

        $bulanPertama = $contract->tanggal_mulai->format('Y-m') === $periode;
        $tanggalTerbit = $bulanPertama ? $contract->tanggal_mulai : $awalPeriode;
        $jumlah = $bulanPertama
            ? $this->calculator->prorate($contract->harga_bulanan, $contract->tanggal_mulai)
            : $contract->harga_bulanan;

        try {
            return Invoice::create([
                'contract_id' => $contract->id,
                'periode' => $periode,
                'tanggal_terbit' => $tanggalTerbit,
                'jatuh_tempo' => $tanggalTerbit->addDays(BillingCalculator::HARI_JATUH_TEMPO),
                'jumlah' => $jumlah,
                'prorata' => $jumlah !== $contract->harga_bulanan,
                'status' => Invoice::STATUS_BELUM_LUNAS,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Index unik (contract_id, periode) menjaga jika dua worker memproses periode yang sama bersamaan.
            return null;
        }
    }
}
