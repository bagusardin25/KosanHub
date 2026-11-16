<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyRoomApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bisa_membuat_properti_dan_kamar(): void
    {
        $property = $this->postJson('/api/properties', [
            'nama' => 'Kos Mawar',
            'alamat' => 'Jl. Mawar No. 1',
            'nama_pemilik' => 'Bu Rina',
        ])->assertCreated()->json('data');

        $this->postJson('/api/rooms', [
            'property_id' => $property['id'],
            'nomor' => 'M1',
            'harga_bulanan' => 1_000_000,
        ])->assertCreated()->assertJsonPath('data.nomor', 'M1');

        $this->getJson("/api/properties/{$property['id']}")
            ->assertOk()
            ->assertJsonCount(1, 'data.rooms');
    }

    public function test_validasi_data_properti_wajib_diisi(): void
    {
        $this->postJson('/api/properties', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama', 'alamat', 'nama_pemilik']);
    }

    public function test_nomor_kamar_tidak_boleh_kembar_dalam_satu_properti(): void
    {
        $room = Room::factory()->create(['nomor' => 'A1']);
        $propertiLain = Property::factory()->create();

        $this->postJson('/api/rooms', [
            'property_id' => $room->property_id,
            'nomor' => 'A1',
            'harga_bulanan' => 1_000_000,
        ])->assertUnprocessable()->assertJsonValidationErrors('nomor');

        // Nomor yang sama di properti lain tetap boleh.
        $this->postJson('/api/rooms', [
            'property_id' => $propertiLain->id,
            'nomor' => 'A1',
            'harga_bulanan' => 1_000_000,
        ])->assertCreated();
    }
}
