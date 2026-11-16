<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractService
{
    /**
     * Membuat kontrak sewa baru.
     * Aturan bisnis: satu kamar hanya boleh punya satu kontrak AKTIF.
     *
     * @param  array{room_id: int, tenant_id: int, tanggal_mulai: string, harga_bulanan?: int|null}  $data
     */
    public function create(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            // Kunci baris kamar supaya dua request bersamaan tidak sama-sama lolos pengecekan.
            $room = Room::query()->lockForUpdate()->findOrFail($data['room_id']);

            if ($room->activeContract()->exists()) {
                throw $this->kamarSudahTerisi();
            }

            try {
                return Contract::create([
                    'room_id' => $room->id,
                    'tenant_id' => $data['tenant_id'],
                    'tanggal_mulai' => $data['tanggal_mulai'],
                    'harga_bulanan' => $data['harga_bulanan'] ?? $room->harga_bulanan,
                    'status' => Contract::STATUS_AKTIF,
                    'active_room_id' => $room->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Pengaman terakhir dari index unik active_room_id di database.
                throw $this->kamarSudahTerisi();
            }
        });
    }

    public function end(Contract $contract, CarbonInterface $tanggalSelesai): Contract
    {
        if ($contract->status !== Contract::STATUS_AKTIF) {
            throw ValidationException::withMessages([
                'contract' => 'Kontrak ini sudah tidak aktif.',
            ]);
        }

        if ($tanggalSelesai->lt($contract->tanggal_mulai)) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => 'Tanggal selesai tidak boleh sebelum tanggal mulai kontrak.',
            ]);
        }

        $contract->update([
            'status' => Contract::STATUS_SELESAI,
            'tanggal_selesai' => $tanggalSelesai,
            'active_room_id' => null,
        ]);

        return $contract;
    }

    private function kamarSudahTerisi(): ValidationException
    {
        return ValidationException::withMessages([
            'room_id' => 'Kamar ini masih memiliki kontrak aktif.',
        ]);
    }
}
