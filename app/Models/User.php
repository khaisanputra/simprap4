<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

protected $fillable = [
        'user_id',
        'username',
        'email',
        'password',
        'nama',
        'role',
        'status',
        'nik',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_telepon',
        'asal_sekolah',
        'alamat_sekolah',
        'jenis_identitas_sekolah',
        'nomor_identitas_sekolah',
        'pkl_mulai',
        'pkl_selesai',
        'ktp_path',
        'kartu_pelajar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password'      => 'hashed',
        'nama'          => 'string',
        'role'          => 'string',
        'status'        => 'string',
        'tanggal_lahir' => 'date',
        'pkl_mulai'     => 'date',
        'pkl_selesai'   => 'date',
    ];

    public function laporans()
    {
        return $this->hasMany(\App\Models\Laporan::class, 'user_id');
    }

    public function laporanSales()
    {
        return $this->hasMany(\App\Models\LaporanSales::class, 'user_id');
    }

    /**
     * Scope: ambil seluruh akun siswa PKL yang masih aktif.
     * Dipakai sebagai penyebut (denominator) pada perhitungan
     * Persentase Kehadiran dashboard.
     */
    public function scopeSiswaAktif($query)
    {
        return $query->where('role', 'siswa')->where('status', 'Aktif');
    }
}
