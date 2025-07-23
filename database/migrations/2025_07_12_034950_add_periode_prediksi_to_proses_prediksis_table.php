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
        Schema::table('proses_prediksis', function (Blueprint $table) {
            // Kolom untuk menyimpan periode yg digunakan, misal: "3-Bulan"
            $table->string('periode_prediksi')->after('tanggal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proses_prediksis', function (Blueprint $table) {
            //
        });
    }
};
