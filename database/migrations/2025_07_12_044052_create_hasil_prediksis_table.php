<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hasil_prediksis', function (Blueprint $table) {
            $table->id();
            // Relasi one-to-one dengan tabel prediksis
            $table->foreignId('prediksi_id')->constrained('prediksis')->onDelete('cascade');
            $table->integer('penjualan_aktual')->nullable();
            $table->integer('stok_aktual');
            $table->integer('prediksi_stok');
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_prediksis');
    }
};
