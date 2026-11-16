<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ArrearsController;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceRun;
use App\Models\Property;
use App\Models\Room;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Halaman ringkasan sederhana untuk dilihat di browser. */
    public function __invoke(): View
    {
        return view('home', [
            'stat' => [
                'properti' => Property::count(),
                'kamar' => Room::count(),
                'kamar_terisi' => Contract::where('status', Contract::STATUS_AKTIF)->count(),
                'tagihan_belum_lunas' => Invoice::where('status', Invoice::STATUS_BELUM_LUNAS)->count(),
            ],
            'tunggakan' => ArrearsController::daftarTunggakan(),
            'runTerakhir' => InvoiceRun::latest('id')->limit(5)->get(),
            'version' => config('app.version'),
        ]);
    }
}
