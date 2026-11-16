<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PropertyController extends Controller
{
    public function index(): array
    {
        return ['data' => Property::withCount('rooms')->orderBy('nama')->get()];
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string', 'max:255'],
            'nama_pemilik' => ['required', 'string', 'max:255'],
        ]);

        return response()->json(['data' => Property::create($data)], 201);
    }

    public function show(Property $property): array
    {
        return ['data' => $property->load('rooms')];
    }

    public function update(Request $request, Property $property): array
    {
        $property->update($request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'alamat' => ['sometimes', 'required', 'string', 'max:255'],
            'nama_pemilik' => ['sometimes', 'required', 'string', 'max:255'],
        ]));

        return ['data' => $property];
    }

    public function destroy(Property $property): Response|JsonResponse
    {
        $punyaKontrak = Contract::query()
            ->whereIn('room_id', $property->rooms()->select('id'))
            ->exists();

        if ($punyaKontrak) {
            return response()->json([
                'message' => 'Properti tidak dapat dihapus karena kamarnya memiliki riwayat kontrak.',
            ], 422);
        }

        $property->delete();

        return response()->noContent();
    }
}
