<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aturan bisnis: satu kamar hanya untuk satu kontrak aktif.
 */
class ContractRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_kamar_hanya_boleh_punya_satu_kontrak_aktif(): void
    {
        $room = Room::factory()->create();
        [$penyewaPertama, $penyewaKedua] = Tenant::factory()->count(2)->create();

        $this->postJson('/api/contracts', [
            'room_id' => $room->id,
            'tenant_id' => $penyewaPertama->id,
            'tanggal_mulai' => '2026-11-01',
        ])->assertCreated()->assertJsonPath('data.status', 'AKTIF');

        $this->postJson('/api/contracts', [
            'room_id' => $room->id,
            'tenant_id' => $penyewaKedua->id,
            'tanggal_mulai' => '2026-11-15',
        ])->assertUnprocessable()->assertJsonValidationErrors('room_id');

        $this->assertDatabaseCount('contracts', 1);
    }

    public function test_kamar_bisa_disewa_lagi_setelah_kontrak_lama_diakhiri(): void
    {
        $room = Room::factory()->create();
        [$penyewaPertama, $penyewaKedua] = Tenant::factory()->count(2)->create();

        $kontrakLama = $this->postJson('/api/contracts', [
            'room_id' => $room->id,
            'tenant_id' => $penyewaPertama->id,
            'tanggal_mulai' => '2026-09-01',
        ])->json('data');

        $this->postJson("/api/contracts/{$kontrakLama['id']}/end", ['tanggal_selesai' => '2026-10-31'])
            ->assertOk()
            ->assertJsonPath('data.status', 'SELESAI');

        $this->postJson('/api/contracts', [
            'room_id' => $room->id,
            'tenant_id' => $penyewaKedua->id,
            'tanggal_mulai' => '2026-11-01',
        ])->assertCreated();
    }

    public function test_harga_kontrak_mengikuti_harga_kamar_saat_kontrak_dibuat(): void
    {
        $room = Room::factory()->create(['harga_bulanan' => 1_500_000]);

        $this->postJson('/api/contracts', [
            'room_id' => $room->id,
            'tenant_id' => Tenant::factory()->create()->id,
            'tanggal_mulai' => '2026-11-01',
        ])->assertCreated()->assertJsonPath('data.harga_bulanan', 1_500_000);
    }
}
