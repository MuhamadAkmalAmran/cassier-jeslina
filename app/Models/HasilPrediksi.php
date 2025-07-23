<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilPrediksi extends Model
{
    use HasFactory;
    protected $fillable = ['prediksi_id', 'penjualan_aktual', 'stok_aktual', 'prediksi_stok', 'tanggal'];

    public function prediksi() { return $this->belongsTo(Prediksi::class); }
}
