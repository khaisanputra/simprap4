<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbsenPulangRequest extends FormRequest
{
    /**
     * Setiap user yang sudah login boleh melakukan absen pulang.
     * Autentikasi dijamin oleh middleware auth pada route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi koordinat GPS & foto absen pulang.
     * Jika GPS tidak diwajibkan (config absensi.gps_wajib = false),
     * koordinat boleh dikosongkan.
     */
    public function rules(): array
    {
        $wajib = config('absensi.gps_wajib', true) ? 'required' : 'nullable';
        $maxKb = config('absensi.max_foto_kb', 10240);

        return [
            'latitude'  => [$wajib, 'numeric', 'between:-90,90'],
            'longitude' => [$wajib, 'numeric', 'between:-180,180'],
            'accuracy'  => [$wajib, 'numeric', 'min:1'],
            'foto'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', "max:{$maxKb}"],
        ];
    }

    /**
     * Pesan kesalahan dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'latitude.required'  => 'Lokasi belum ditemukan. Silakan aktifkan GPS dan coba lagi.',
            'latitude.numeric'   => 'Koordinat latitude tidak valid.',
            'latitude.between'   => 'Koordinat latitude tidak valid.',
            'longitude.required' => 'Lokasi belum ditemukan. Silakan aktifkan GPS dan coba lagi.',
            'longitude.numeric'  => 'Koordinat longitude tidak valid.',
            'longitude.between'  => 'Koordinat longitude tidak valid.',
            'accuracy.required'  => 'Akurasi lokasi belum didapatkan. Silakan coba lagi.',
            'accuracy.numeric'   => 'Akurasi lokasi tidak valid.',
            'accuracy.min'       => 'Akurasi lokasi tidak valid.',
            'foto.image'         => 'File foto harus berupa gambar yang valid.',
            'foto.mimes'         => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'foto.max'           => 'Ukuran foto terlalu besar. Maksimal ukuran foto adalah 10 MB.',
        ];
    }
}
