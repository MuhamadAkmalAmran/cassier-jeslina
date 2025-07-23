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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id(); // id_pembayaran
            $table->foreignId('transaksi_id')->constrained('transaksis')->onDelete('cascade');
            $table->decimal('total_pembayaran', 15, 2);
            $table->decimal('dibayar', 15, 2);
            $table->decimal('kembalian', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
