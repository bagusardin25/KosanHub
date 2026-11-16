<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): array
    {
        $invoices = Invoice::query()
            ->with(['contract.tenant:id,nama', 'contract.room:id,property_id,nomor'])
            ->when($request->query('periode'), fn ($q, $periode) => $q->where('periode', $periode))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('contract_id'), fn ($q, $id) => $q->where('contract_id', $id))
            ->orderByDesc('periode')
            ->orderBy('id')
            ->get();

        return ['data' => $invoices];
    }

    public function show(Invoice $invoice): array
    {
        $invoice->load(['contract.tenant', 'contract.room.property', 'payment']);

        return [
            'data' => $invoice,
            // Perkiraan denda jika dibayar hari ini (hanya untuk tagihan yang belum lunas).
            'jika_dibayar_hari_ini' => $invoice->status === Invoice::STATUS_BELUM_LUNAS
                ? $invoice->rincianPembayaran(now())
                : null,
        ];
    }
}
