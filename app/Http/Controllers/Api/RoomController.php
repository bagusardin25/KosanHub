<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request): array
    {
        $rooms = Room::query()
            ->with(['property:id,nama', 'activeContract.tenant:id,nama'])
            ->when($request->query('property_id'), fn ($q, $id) => $q->where('property_id', $id))
            ->orderBy('property_id')
            ->orderBy('nomor')
            ->get();

        return ['data' => $rooms];
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'nomor' => [
                'required', 'string', 'max:20',
                Rule::unique('rooms')->where('property_id', $request->input('property_id')),
            ],
            'harga_bulanan' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(['data' => Room::create($data)], 201);
    }

    public function show(Room $room): array
    {
        return ['data' => $room->load(['property', 'activeContract.tenant'])];
    }

    public function update(Request $request, Room $room): array
    {
        $room->update($request->validate([
            'nomor' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('rooms')->where('property_id', $room->property_id)->ignore($room),
            ],
            'harga_bulanan' => ['sometimes', 'required', 'integer', 'min:1'],
        ]));

        return ['data' => $room];
    }

    public function destroy(Room $room): Response|JsonResponse
    {
        if ($room->contracts()->exists()) {
            return response()->json([
                'message' => 'Kamar tidak dapat dihapus karena memiliki riwayat kontrak.',
            ], 422);
        }

        $room->delete();

        return response()->noContent();
    }
}
