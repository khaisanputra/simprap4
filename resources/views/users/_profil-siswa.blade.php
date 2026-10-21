@php
    $user = $user ?? null;
    $isEdit = $isEdit ?? false;
    $tampilProfil = old('role', $user->role ?? null) === 'siswa';
    $tanggalLahir = old('tanggal_lahir', $user?->tanggal_lahir?->format('Y-m-d'));
    $pklMulai = old('pkl_mulai', $user?->pkl_mulai?->format('Y-m-d'));
    $pklSelesai = old('pkl_selesai', $user?->pkl_selesai?->format('Y-m-d'));
@endphp

<div id="profilSiswa" style="display: {{ $tampilProfil ? '' : 'none' }};">
    <div style="margin: 22px 0 14px; padding-top: 18px; border-top: 1px solid var(--border);">
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main);"><i class="fa-solid fa-id-card" style="color: var(--primary); margin-right: 6px;"></i> Identitas Pribadi</h3>
        <p style="font-size: 0.8rem; color: var(--text-sec); margin-top: 3px;">Lengkapi data pribadi siswa PKL.</p>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="nik" class="form-label">NIK</label>
            <div class="input-group">
                <input type="text" class="form-control" id="nik" name="nik" value="{{ old('nik', $user->nik ?? '') }}" placeholder="16 digit NIK" maxlength="16" inputmode="numeric" pattern="[0-9]{16}">
                <i class="fa-solid fa-id-card input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
            <div class="input-group">
                <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir', $user->tempat_lahir ?? '') }}" placeholder="Kota tempat lahir" maxlength="100">
                <i class="fa-solid fa-location-dot input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
            <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="{{ $tanggalLahir }}">
        </div>

        <div class="form-group">
            <label for="no_telepon" class="form-label">Nomor Telepon</label>
            <div class="input-group">
                <input type="text" class="form-control" id="no_telepon" name="no_telepon" value="{{ old('no_telepon', $user->no_telepon ?? '') }}" placeholder="08xxxxxxxxxx" maxlength="20">
                <i class="fa-solid fa-phone input-icon"></i>
            </div>
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
            <label for="alamat" class="form-label">Alamat Lengkap</label>
            <textarea class="form-control" id="alamat" name="alamat" rows="3" maxlength="1000" placeholder="Alamat lengkap sesuai identitas" style="height: auto; min-height: 84px; padding: 12px 14px; resize: vertical;">{{ old('alamat', $user->alamat ?? '') }}</textarea>
        </div>
    </div>

    <div style="margin: 24px 0 14px; padding-top: 18px; border-top: 1px solid var(--border);">
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main);"><i class="fa-solid fa-school" style="color: var(--primary); margin-right: 6px;"></i> Informasi Sekolah</h3>
        <p style="font-size: 0.8rem; color: var(--text-sec); margin-top: 3px;">Data sekolah asal dan identitas siswa di sekolah.</p>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="asal_sekolah" class="form-label">Asal Sekolah</label>
            <div class="input-group">
                <input type="text" class="form-control" id="asal_sekolah" name="asal_sekolah" value="{{ old('asal_sekolah', $user->asal_sekolah ?? '') }}" placeholder="Nama sekolah" maxlength="150">
                <i class="fa-solid fa-school input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label for="jenis_identitas_sekolah" class="form-label">Jenis Identitas Sekolah</label>
            <select class="form-control" id="jenis_identitas_sekolah" name="jenis_identitas_sekolah">
                <option value="" disabled {{ old('jenis_identitas_sekolah', $user->jenis_identitas_sekolah ?? '') ? '' : 'selected' }}>Pilih jenis identitas</option>
                <option value="NISN" {{ old('jenis_identitas_sekolah', $user->jenis_identitas_sekolah ?? '') === 'NISN' ? 'selected' : '' }}>NISN</option>
                <option value="NRP" {{ old('jenis_identitas_sekolah', $user->jenis_identitas_sekolah ?? '') === 'NRP' ? 'selected' : '' }}>NRP</option>
                <option value="NIP" {{ old('jenis_identitas_sekolah', $user->jenis_identitas_sekolah ?? '') === 'NIP' ? 'selected' : '' }}>NIP</option>
            </select>
        </div>

        <div class="form-group">
            <label for="nomor_identitas_sekolah" class="form-label">Nomor Identitas Sekolah</label>
            <div class="input-group">
                <input type="text" class="form-control" id="nomor_identitas_sekolah" name="nomor_identitas_sekolah" value="{{ old('nomor_identitas_sekolah', $user->nomor_identitas_sekolah ?? '') }}" placeholder="Nomor sesuai jenis identitas" maxlength="30">
                <i class="fa-solid fa-hashtag input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Keterangan</label>
            <small style="font-size: 0.75rem; color: var(--text-sec); line-height: 1.5; display: block; padding-top: 10px;">Pilih NISN, NRP, atau NIP sesuai identitas yang dimiliki siswa. Nomor disimpan sebagai teks agar angka nol di depan tetap utuh.</small>
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
            <label for="alamat_sekolah" class="form-label">Alamat Sekolah</label>
            <textarea class="form-control" id="alamat_sekolah" name="alamat_sekolah" rows="3" maxlength="1000" placeholder="Alamat lengkap sekolah" style="height: auto; min-height: 84px; padding: 12px 14px; resize: vertical;">{{ old('alamat_sekolah', $user->alamat_sekolah ?? '') }}</textarea>
        </div>
    </div>

    <div style="margin: 24px 0 14px; padding-top: 18px; border-top: 1px solid var(--border);">
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main);"><i class="fa-solid fa-calendar-days" style="color: var(--primary); margin-right: 6px;"></i> Periode PKL</h3>
        <p style="font-size: 0.8rem; color: var(--text-sec); margin-top: 3px;">Tentukan tanggal mulai dan selesai pelaksanaan PKL.</p>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="pkl_mulai" class="form-label">Tanggal Mulai PKL</label>
            <input type="date" class="form-control" id="pkl_mulai" name="pkl_mulai" value="{{ $pklMulai }}">
        </div>

        <div class="form-group">
            <label for="pkl_selesai" class="form-label">Tanggal Selesai PKL</label>
            <input type="date" class="form-control" id="pkl_selesai" name="pkl_selesai" value="{{ $pklSelesai }}">
        </div>
    </div>

    <div style="margin: 24px 0 14px; padding-top: 18px; border-top: 1px solid var(--border);">
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main);"><i class="fa-solid fa-folder-open" style="color: var(--primary); margin-right: 6px;"></i> Dokumen Identitas</h3>
        <p style="font-size: 0.8rem; color: var(--text-sec); margin-top: 3px;">Format JPEG, PNG, atau WebP. Maksimal 2 MB per dokumen. File disimpan pada storage privat.</p>
    </div>

    @if($isEdit && $user)
    <div class="form-grid" style="margin-bottom: 14px;">
        <div class="form-group">
            <label class="form-label">KTP Saat Ini</label>
            @if($user->ktp_path)
                <a href="{{ route('users.document', [$user, 'ktp']) }}" target="_blank" rel="noopener" class="btn-tool" style="text-decoration: none; width: max-content;"><i class="fa-solid fa-eye"></i> Lihat KTP</a>
            @else
                <span style="font-size: 0.85rem; color: var(--text-sec);">Belum diunggah</span>
            @endif
        </div>
        <div class="form-group">
            <label class="form-label">Kartu Pelajar Saat Ini</label>
            @if($user->kartu_pelajar_path)
                <a href="{{ route('users.document', [$user, 'kartu-pelajar']) }}" target="_blank" rel="noopener" class="btn-tool" style="text-decoration: none; width: max-content;"><i class="fa-solid fa-eye"></i> Lihat Kartu Pelajar</a>
            @else
                <span style="font-size: 0.85rem; color: var(--text-sec);">Belum diunggah</span>
            @endif
        </div>
    </div>
    @endif

    <div class="form-grid">
        <div class="form-group">
            <label for="ktp" class="form-label">{{ $isEdit ? 'Ganti KTP (opsional)' : 'Upload KTP' }}</label>
            <input type="file" class="form-control" id="ktp" name="ktp" accept="image/jpeg,image/png,image/webp" style="height: auto; padding: 10px 12px;">
            <small style="font-size: 0.72rem; color: var(--text-sec);">JPEG, PNG, atau WebP. Maksimal 2 MB.</small>
        </div>

        <div class="form-group">
            <label for="kartu_pelajar" class="form-label">{{ $isEdit ? 'Ganti Kartu Pelajar (opsional)' : 'Upload Kartu Pelajar' }}</label>
            <input type="file" class="form-control" id="kartu_pelajar" name="kartu_pelajar" accept="image/jpeg,image/png,image/webp" style="height: auto; padding: 10px 12px;">
            <small style="font-size: 0.72rem; color: var(--text-sec);">JPEG, PNG, atau WebP. Maksimal 2 MB.</small>
        </div>
    </div>
</div>
