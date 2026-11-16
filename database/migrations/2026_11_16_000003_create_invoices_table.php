<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->char('periode', 7); // format YYYY-MM
            $table->date('tanggal_terbit');
            $table->date('jatuh_tempo');
            $table->unsignedInteger('jumlah');
            $table->boolean('prorata')->default(false);
            $table->string('status', 20)->default('BELUM_LUNAS');
            $table->timestamps();

            // Aturan bisnis: tagihan tidak boleh terbit dua kali untuk periode yang sama.
            $table->unique(['contract_id', 'periode']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Satu tagihan dilunasi sekali (pembayaran penuh).
            $table->foreignId('invoice_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('tanggal_bayar');
            $table->unsignedInteger('jumlah_pokok');
            $table->unsignedTinyInteger('persen_denda');
            $table->unsignedInteger('denda');
            $table->unsignedInteger('total');
            $table->string('metode', 20);
            $table->timestamps();
        });

        Schema::create('invoice_runs', function (Blueprint $table) {
            $table->id();
            $table->char('periode', 7);
            $table->string('status', 20)->default('PENDING');
            $table->unsignedInteger('jumlah_dibuat')->default(0);
            $table->unsignedInteger('jumlah_dilewati')->default(0);
            $table->text('pesan_error')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_runs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
    }
};
