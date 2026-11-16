<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\ContractService;
use App\Services\InvoiceGenerator;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;

/**
 * Data contoh KosanHub.
 *
 * Seeder ini sengaja tidak memakai factory/Faker (Faker tidak terpasang di image production)
 * dan aman dijalankan berulang kali karena memakai updateOrCreate/firstOrCreate.
 * Entrypoint container menjalankannya setiap deploy.
 */
class DatabaseSeeder extends Seeder
{
    public function run(ContractService $contracts, InvoiceGenerator $generator, PaymentService $payments): void
    {
        if (Property::exists()) {
            $this->command?->info('Data contoh sudah ada, seeder dilewati.');

            return;
        }

        $melati = Property::updateOrCreate(
            ['nama' => 'Kos Melati Putri'],
            ['alamat' => 'Jl. Melati No. 12', 'nama_pemilik' => 'Bu Sari'],
        );
        $kenanga = Property::updateOrCreate(
            ['nama' => 'Kos Kenanga Putra'],
            ['alamat' => 'Jl. Kenanga No. 7', 'nama_pemilik' => 'Pak Hadi'],
        );

        $kamar = [];
        foreach (['A1', 'A2', 'A3'] as $nomor) {
            $kamar[$nomor] = Room::updateOrCreate(
                ['property_id' => $melati->id, 'nomor' => $nomor],
                ['harga_bulanan' => 1_200_000],
            );
        }
        foreach (['B1', 'B2', 'B3'] as $nomor) {
            $kamar[$nomor] = Room::updateOrCreate(
                ['property_id' => $kenanga->id, 'nomor' => $nomor],
                ['harga_bulanan' => 900_000],
            );
        }

        $penyewa = [];
        foreach ([
            ['nama' => 'Andi Pratama', 'telepon' => '081200000001', 'email' => 'andi@example.com'],
            ['nama' => 'Budi Santoso', 'telepon' => '081200000002', 'email' => 'budi@example.com'],
            ['nama' => 'Citra Lestari', 'telepon' => '081200000003', 'email' => 'citra@example.com'],
            ['nama' => 'Dewi Anggraini', 'telepon' => '081200000004', 'email' => 'dewi@example.com'],
        ] as $data) {
            $penyewa[$data['nama']] = Tenant::updateOrCreate(['email' => $data['email']], $data);
        }

        // Kontrak relatif terhadap tanggal seeding pertama supaya datanya terlihat "baru".
        $bulanIni = now()->startOfMonth();
        $kontrakContoh = [
            ['kamar' => 'A1', 'penyewa' => 'Andi Pratama', 'mulai' => $bulanIni->subMonths(2)],
            ['kamar' => 'A2', 'penyewa' => 'Budi Santoso', 'mulai' => $bulanIni->subMonth()->addDays(14)],
            ['kamar' => 'B1', 'penyewa' => 'Citra Lestari', 'mulai' => $bulanIni->subMonth()->addDays(20)],
        ];

        foreach ($kontrakContoh as $k) {
            $room = $kamar[$k['kamar']];
            $sudahAda = Contract::where('room_id', $room->id)
                ->where('tenant_id', $penyewa[$k['penyewa']]->id)
                ->exists();

            if (! $sudahAda && ! $room->activeContract()->exists()) {
                $contracts->create([
                    'room_id' => $room->id,
                    'tenant_id' => $penyewa[$k['penyewa']]->id,
                    'tanggal_mulai' => $k['mulai']->toDateString(),
                ]);
            }
        }

        // Tagihan dua bulan terakhir (Budi & Citra kena prorata di bulan pertama).
        $periodeContoh = [$bulanIni->subMonths(2)->format('Y-m'), $bulanIni->subMonth()->format('Y-m')];
        foreach ($periodeContoh as $periode) {
            $generator->generateForPeriod($periode);
        }

        // Andi selalu bayar tepat waktu; tagihan Budi & Citra dibiarkan supaya muncul di daftar tunggakan.
        $tagihanAndi = Invoice::query()
            ->whereIn('periode', $periodeContoh)
            ->where('status', Invoice::STATUS_BELUM_LUNAS)
            ->whereHas('contract', fn ($q) => $q->where('tenant_id', $penyewa['Andi Pratama']->id))
            ->get();

        foreach ($tagihanAndi as $invoice) {
            $payments->pay($invoice, $invoice->jatuh_tempo->subDays(2), 'TRANSFER');
        }
    }
}
