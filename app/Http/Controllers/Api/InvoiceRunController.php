<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMonthlyInvoices;
use App\Models\InvoiceRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceRunController extends Controller
{
    public function index(): array
    {
        return ['data' => InvoiceRun::latest('id')->limit(20)->get()];
    }

    /**
     * POST /api/invoice-runs — memasukkan job pembuatan tagihan ke antrean.
     * Balasan 202 (Accepted) karena tagihan dibuat oleh container worker, bukan di request ini.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'periode' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $run = InvoiceRun::create([
            'periode' => $data['periode'] ?? now()->format('Y-m'),
            'status' => InvoiceRun::STATUS_PENDING,
        ]);

        GenerateMonthlyInvoices::dispatch($run->id);

        return response()->json(['data' => $run->fresh()], 202);
    }

    public function show(InvoiceRun $invoiceRun): array
    {
        return ['data' => $invoiceRun];
    }
}
