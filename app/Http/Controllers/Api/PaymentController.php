<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    /** POST /api/invoices/{invoice}/payments — melunasi tagihan (denda dihitung server). */
    public function store(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'tanggal_bayar' => ['nullable', 'date_format:Y-m-d'],
            'metode' => ['required', Rule::in(Payment::METODE)],
        ]);

        $tanggalBayar = isset($data['tanggal_bayar'])
            ? Date::createFromFormat('!Y-m-d', $data['tanggal_bayar'])
            : now()->startOfDay();

        $payment = $this->payments->pay($invoice, $tanggalBayar, $data['metode']);

        return response()->json(['data' => $payment->load('invoice')], 201);
    }
}
