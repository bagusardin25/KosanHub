<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * GET /health — dipakai healthcheck Docker dan stage Smoke Test di Jenkins.
     * 200 jika database terhubung, 503 jika tidak (supaya pipeline gagal).
     */
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
            $database = 'connected';
        } catch (Throwable) {
            $database = 'disconnected';
        }

        $sehat = $database === 'connected';

        return response()->json([
            'status' => $sehat ? 'ok' : 'error',
            'database' => $database,
            'app' => config('app.name'),
            'version' => config('app.version'),
            'timestamp' => now()->toIso8601String(),
        ], $sehat ? 200 : 503);
    }
}
