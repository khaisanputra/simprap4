<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLokasiKantorRequest extends FormRequest
{
    /**
     * Hanya admin (route dilindungi middleware role:admin).
     * Sumber role tunggal: authenticated Laravel user (Auth) saja.
     * Tidak ada fallback ke session user_id.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->role ?? null) === 'admin';
    }

    /**
     * Aturan validasi Pengaturan Lokasi Kantor & jam buka Absen Masuk.
     */
    public function rules(): array
    {
        return [
            'nama_kantor'   => ['required', 'string', 'max:200'],
            'alamat_kantor' => ['required', 'string', 'max:500'],
            'latitude'      => ['required', 'numeric', 'between:-90,90'],
            'longitude'     => ['required', 'numeric', 'between:-180,180'],
            'radius_kantor' => ['required', 'integer', 'min:10', 'max:50000'],
            'jam_mulai'     => ['required', 'date_format:H:i'],
            'jam_selesai'   => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'jam_pulang_mulai'   => ['required', 'date_format:H:i'],
            'jam_pulang_selesai' => ['required', 'date_format:H:i', 'after:jam_pulang_mulai'],
            'jam_laporan_mulai'   => ['nullable', 'date_format:H:i'],
            'jam_laporan_selesai' => ['nullable', 'date_format:H:i', 'after:jam_laporan_mulai'],
        ];
    }

    /**
     * Pesan kesalahan dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'nama_kantor.required'   => 'Nama kantor wajib diisi.',
            'nama_kantor.max'        => 'Nama kantor maksimal 200 karakter.',
            'alamat_kantor.required' => 'Alamat kantor wajib diisi.',
            'alamat_kantor.max'      => 'Alamat kantor maksimal 500 karakter.',
            'latitude.required'      => 'Latitude kantor wajib diisi.',
            'latitude.numeric'       => 'Latitude kantor tidak valid.',
            'latitude.between'       => 'Latitude kantor harus antara -90 sampai 90.',
            'longitude.required'     => 'Longitude kantor wajib diisi.',
            'longitude.numeric'      => 'Longitude kantor tidak valid.',
            'longitude.between'      => 'Longitude kantor harus antara -180 sampai 180.',
            'radius_kantor.required' => 'Radius kantor wajib diisi.',
            'radius_kantor.integer'  => 'Radius kantor harus berupa angka (meter).',
            'radius_kantor.min'      => 'Radius kantor minimal 10 meter.',
            'radius_kantor.max'      => 'Radius kantor maksimal 50.000 meter.',
            'jam_mulai.required'     => 'Jam mulai Absen Masuk wajib diisi.',
            'jam_mulai.date_format'  => 'Jam mulai Absen Masuk harus format 24 jam (HH:MM).',
            'jam_selesai.required'   => 'Jam selesai Absen Masuk wajib diisi.',
            'jam_selesai.date_format' => 'Jam selesai Absen Masuk harus format 24 jam (HH:MM).',
            'jam_selesai.after'      => 'Jam selesai harus lebih besar dari jam mulai.',
            'jam_pulang_mulai.required'  => 'Jam Pulang Mulai wajib diisi.',
            'jam_pulang_mulai.date_format' => 'Jam Pulang Mulai harus format 24 jam (HH:MM).',
            'jam_pulang_selesai.required'  => 'Jam Pulang Selesai wajib diisi.',
            'jam_pulang_selesai.date_format' => 'Jam Pulang Selesai harus format 24 jam (HH:MM).',
            'jam_pulang_selesai.after'  => 'Jam Pulang Selesai harus lebih besar dari Jam Pulang Mulai.',
            'jam_laporan_mulai.date_format' => 'Jam Laporan Mulai harus format 24 jam (HH:MM).',
            'jam_laporan_selesai.date_format' => 'Jam Laporan Selesai harus format 24 jam (HH:MM).',
            'jam_laporan_selesai.after'  => 'Jam Laporan Selesai harus lebih besar dari Jam Laporan Mulai.',
        ];
    }
}