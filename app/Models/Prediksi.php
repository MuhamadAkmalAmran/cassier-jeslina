<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prediksi extends Model
{
    use HasFactory;
    protected $fillable = ['barang_id', 'periode_data', 'tanggal_prediksi', 'status'];

    public function barang() { return $this->belongsTo(Barang::class); }
    public function hasil() { return $this->hasMany(HasilPrediksi::class); }
}
