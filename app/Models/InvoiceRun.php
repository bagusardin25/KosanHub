<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Catatan satu kali proses pembuatan tagihan bulanan yang dikerjakan oleh container worker.
 * Dipakai Smoke Test di Jenkins untuk membuktikan worker benar-benar memproses job.
 */
#[Fillable(['periode', 'status', 'jumlah_dibuat', 'jumlah_dilewati', 'pesan_error', 'selesai_pada'])]
class InvoiceRun extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_DONE = 'DONE';

    public const STATUS_FAILED = 'FAILED';

    protected function casts(): array
    {
        return [
            'jumlah_dibuat' => 'integer',
            'jumlah_dilewati' => 'integer',
            'selesai_pada' => 'datetime',
        ];
    }
}
