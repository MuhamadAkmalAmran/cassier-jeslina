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
        Schema::create('proses_prediksis', function (Blueprint $table) {
            $table->id(); // id_Prediksi
            $table->foreignId('barang_id')->constrained('barangs');
            $table->date('tanggal');
            $table->integer('penjualan_aktual');
            $table->integer('stok_aktual');
            $table->integer('prediksi_stok');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proses_prediksis');
    }
};
