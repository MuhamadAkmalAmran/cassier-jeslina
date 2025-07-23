<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsesPrediksi extends Model
{
    use HasFactory;

    protected $fillable = ['barang_id', 'tanggal', 'penjualan_aktual', 'stok_aktual', 'prediksi_stok', 'periode_prediksi'];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }
}
