<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class ContractController extends Controller
{
    public function __construct(private readonly ContractService $contracts) {}

    public function index(Request $request): array
    {
        $contracts = Contract::query()
            ->with(['room.property:id,nama', 'tenant:id,nama'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest('tanggal_mulai')
            ->get();

        return ['data' => $contracts];
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'harga_bulanan' => ['nullable', 'integer', 'min:1'],
        ]);

        $contract = $this->contracts->create($data);

        return response()->json(['data' => $contract->load(['room', 'tenant'])], 201);
    }

    public function show(Contract $contract): array
    {
        return ['data' => $contract->load(['room.property', 'tenant', 'invoices'])];
    }

    /** POST /api/contracts/{contract}/end — mengakhiri kontrak sehingga kamar bisa disewa lagi. */
    public function end(Request $request, Contract $contract): array
    {
        $data = $request->validate([
            'tanggal_selesai' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $tanggalSelesai = isset($data['tanggal_selesai'])
            ? Date::createFromFormat('!Y-m-d', $data['tanggal_selesai'])
            : now()->startOfDay();

        return ['data' => $this->contracts->end($contract, $tanggalSelesai)];
    }
}
