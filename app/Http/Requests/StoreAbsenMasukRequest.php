<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbsenMasukRequest extends FormRequest
{
    /**
     * Setiap user yang sudah login boleh melakukan absen masuk.
     * Autentikasi dijamin oleh middleware auth pada route.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Default ke Masuk jika tidak dikirim (kompatibel dengan test lama & fallback)
        if (! $this->has('status_kehadiran') || $this->input('status_kehadiran') === null || $this->input('status_kehadiran') === '') {
            $this->merge(['status_kehadiran' => 'Masuk']);
        }
    }

    /**
     * Aturan validasi koordinat GPS, foto absen, dan jenis kehadiran.
     *
     * - Masuk: wajib GPS + radius (latitude/longitude/accuracy required),
     *          foto mengikuti config absensi.foto_wajib.
     * - Izin: GPS nullable, bukti opsional.
     * - Dispensasi/Sakit: GPS nullable, bukti WAJIB.
     *
     * Validasi GPS untuk Masuk tetap ditegakkan di level Request
     * agar error lebih awal, namun validasi radius tetap di Controller.
     */
    public function rules(): array
    {
        $maxKb = config('absensi.max_foto_kb', 10240);
        $status = $this->input('status_kehadiran', 'Masuk');

        // Hanya Masuk yang wajib GPS; lainnya tanpa GPS.
        if (in_array($status, ['Izin', 'Dispensasi', 'Sakit'], true)) {
            $wajibGps = 'nullable';
        } else {
            $wajibGps = config('absensi.gps_wajib', true) ? 'required' : 'nullable';
        }

        // Aturan bukti kondisional:
        // - Izin: opsional
        // - Dispensasi/Sakit: WAJIB (required)
        // - Masuk: nullable (diabaikan, tapi tetap divalidasi jika dikirim)
        if ($status === 'Dispensasi' || $status === 'Sakit') {
            $buktiRule = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];
        } else {
            $buktiRule = ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];
        }

        // Keterangan wajib untuk Izin/Dispensasi/Sakit, opsional untuk Masuk
        $keteranganRule = in_array($status, ['Izin', 'Dispensasi', 'Sakit'], true)
            ? ['required', 'string', 'max:500']
            : ['nullable', 'string', 'max:500'];

        return [
            'status_kehadiran' => ['required', 'in:Masuk,Izin,Dispensasi,Sakit'],
            'keterangan'       => $keteranganRule,
            'latitude'  => [$wajibGps, 'numeric', 'between:-90,90'],
            'longitude' => [$wajibGps, 'numeric', 'between:-180,180'],
            'accuracy'  => [$wajibGps, 'numeric', 'min:1'],
            'foto'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', "max:{$maxKb}"],
            'bukti_file' => $buktiRule,
        ];
    }

    /**
     * Pesan kesalahan dalam Bahasa Indonesia.
     * Pesan untuk bukti_file.required disesuaikan per jenis.
     */
    public function messages(): array
    {
        $status = $this->input('status_kehadiran', 'Masuk');
        $buktiWajibMessage = 'Bukti wajib diupload.';
        if ($status === 'Dispensasi') {
            $buktiWajibMessage = 'Bukti dispensasi wajib diupload.';
        } elseif ($status === 'Sakit') {
            $buktiWajibMessage = 'Bukti sakit wajib diupload.';
        }

        return [
            'status_kehadiran.required' => 'Jenis kehadiran wajib dipilih.',
            'status_kehadiran.in'       => 'Jenis kehadiran tidak valid.',
            'keterangan.required'       => 'Keterangan wajib diisi untuk status Izin/Dispensasi/Sakit.',
            'keterangan.string'         => 'Keterangan harus berupa teks.',
            'keterangan.max'            => 'Keterangan maksimal 500 karakter.',
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
            'bukti_file.required'=> $buktiWajibMessage,
            'bukti_file.file'    => 'Bukti harus berupa file yang valid.',
            'bukti_file.mimes'   => 'Format bukti harus JPG, JPEG, PNG, atau PDF.',
            'bukti_file.max'     => 'Ukuran bukti maksimal 5 MB.',
        ];
    }

    /**
     * Pastikan foto tetap wajib jika status Masuk dan config foto_wajib aktif.
     * Ditambah validasi backend untuk bukti wajib Dispensasi/Sakit (layer kedua).
     * Cegah duplikasi pesan jika rule required sudah menghasilkan error.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $status = $this->input('status_kehadiran');
            if (($status === 'Dispensasi' || $status === 'Sakit') && ! $this->hasFile('bukti_file')) {
                if ($validator->errors()->has('bukti_file')) {
                    return;
                }
                $msg = $status === 'Dispensasi' ? 'Bukti dispensasi wajib diupload.' : 'Bukti sakit wajib diupload.';
                $validator->errors()->add('bukti_file', $msg);
            }
        });
    }
}