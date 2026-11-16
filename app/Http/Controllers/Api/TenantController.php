<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TenantController extends Controller
{
    public function index(): array
    {
        return ['data' => Tenant::orderBy('nama')->get()];
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'telepon' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        return response()->json(['data' => Tenant::create($data)], 201);
    }

    public function show(Tenant $tenant): array
    {
        return ['data' => $tenant->load('contracts.room.property')];
    }

    public function update(Request $request, Tenant $tenant): array
    {
        $tenant->update($request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'telepon' => ['sometimes', 'required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ]));

        return ['data' => $tenant];
    }

    public function destroy(Tenant $tenant): Response|JsonResponse
    {
        if ($tenant->contracts()->exists()) {
            return response()->json([
                'message' => 'Penyewa tidak dapat dihapus karena memiliki riwayat kontrak.',
            ], 422);
        }

        $tenant->delete();

        return response()->noContent();
    }
}
