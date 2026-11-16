<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            // Harga disalin dari kamar saat kontrak dibuat, supaya perubahan harga kamar
            // tidak mengubah tagihan kontrak yang sudah berjalan.
            $table->unsignedInteger('harga_bulanan');
            $table->string('status', 20)->default('AKTIF');

            // Aturan bisnis "satu kamar hanya untuk satu kontrak aktif" juga dijaga di level
            // database: kolom ini berisi room_id selama kontrak AKTIF dan NULL setelah selesai.
            // Index unik mengizinkan banyak NULL, tetapi hanya satu nilai room_id yang aktif.
            $table->unsignedBigInteger('active_room_id')->nullable()->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
