<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('alamat');
            $table->string('nama_pemilik');
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('nomor', 20);
            $table->unsignedInteger('harga_bulanan');
            $table->timestamps();

            // Nomor kamar unik di dalam satu properti.
            $table->unique(['property_id', 'nomor']);
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('telepon', 20);
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('properties');
    }
};
