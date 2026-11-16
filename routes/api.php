<?php

use App\Http\Controllers\Api\ArrearsController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceRunController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TenantController;
use Illuminate\Support\Facades\Route;

// Semua route di file ini otomatis berawalan /api.

Route::apiResource('properties', PropertyController::class);
Route::apiResource('rooms', RoomController::class);
Route::apiResource('tenants', TenantController::class);

Route::apiResource('contracts', ContractController::class)->only(['index', 'store', 'show']);
Route::post('contracts/{contract}/end', [ContractController::class, 'end']);

Route::apiResource('invoice-runs', InvoiceRunController::class)->only(['index', 'store', 'show']);

Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show']);
Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store']);

Route::get('arrears', ArrearsController::class);
