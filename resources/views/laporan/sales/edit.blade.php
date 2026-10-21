<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Laporan Sales - Sistem Absensi PKL Sandya Network</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    <style>
        .laporan-form-card { background:#fff; border:1px solid #E5E7EB; border-radius:18px; box-shadow:0 10px 30px rgba(15,76,129,0.08); padding:22px; margin-bottom:22px; }
        .laporan-form-card h3 { font-size:1.05rem; font-weight:700; color:#0F4C81; margin-bottom:14px; display:flex; align-items:center; gap:10px; }
        .laporan-form-card h3 i { color:#0F4C81; }
        .identitas-box { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; background:#F8FAFC; border:1px solid #E5E7EB; border-radius:12px; padding:14px 16px; margin-bottom:16px; }
        .ident-item { display:flex; flex-direction:column; gap:3px; }
        .ident-item .label { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.06em; color:#6B7280; font-weight:600; }
        .ident-item .value { font-size:0.9rem; font-weight:600; color:#1F2937; word-break:break-word; }
        .textarea-laporan { width:100%; min-height:140px; border:1px solid #E5E7EB; border-radius:12px; padding:12px 14px; font-family:inherit; font-size:0.92rem; color:#1F2937; background:#F8FAFC; outline:none; resize:vertical; line-height:1.7; transition:border-color .2s, box-shadow .2s, background .2s; }
        .textarea-laporan:focus { border-color:#3B82F6; box-shadow:0 0 0 3px rgba(59,130,246,0.15); background:#fff; }
        .foto-input-wrap { border:1px dashed #CBD5E1; background:#F8FAFC; border-radius:12px; padding:14px; transition:border-color .2s; }
        .foto-input-wrap:hover { border-color:#3B82F6; }
        .foto-lama-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); gap:10px; margin-top:10px; }
        .foto-lama-grid img { width:100%; height:90px; object-fit:cover; border-radius:10px; border:1px solid #E5E7EB; }
        @media(max-width:640px){ .laporan-form-card{padding:16px;} }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-logo"><img src="{{ asset('images/logo-sandya2.png') }}" alt="Sandya Absensi PKL"></div>
                <div class="brand-text"><span class="brand-name">Sandya</span><span class="brand-sub">Absensi PKL</span></div>
                <button class="sidebar-close" id="sidebarClose" aria-label="Tutup menu"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <nav class="sidebar-nav">
                <p class="nav-section">Menu Utama</p>
                <a href="{{ route('dashboard') }}" class="nav-link" data-tooltip="Dashboard"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
                <a href="{{ route('absen.masuk') }}" class="nav-link" data-tooltip="Absen Masuk"><i class="fa-solid fa-arrow-right-to-bracket" style="color:#10B981"></i><span>Absen Masuk</span></a>
                <a href="{{ route('absen.pulang') }}" class="nav-link" data-tooltip="Absen Pulang"><i class="fa-solid fa-arrow-right-from-bracket" style="color:#F87171"></i><span>Absen Pulang</span></a>
                <a href="{{ route('laporan.index') }}" class="nav-link active" data-tooltip="Laporan"><i class="fa-solid fa-file-lines"></i><span>Laporan</span></a>
                @if (($role ?? $userSession['role'] ?? 'siswa') !== 'admin')
                <a href="{{ route('rekap.siswa') }}" class="nav-link" data-tooltip="Riwayat Rekap"><i class="fa-solid fa-chart-column"></i><span>Riwayat Rekap</span></a>
                @endif
                <p class="nav-section">Lainnya</p>
                @if (($role ?? $userSession['role'] ?? 'siswa') === 'admin')
                <a href="{{ route('data-absen-masuk.index') }}" class="nav-link" data-tooltip="Data Absen"><i class="fa-solid fa-clipboard-check"></i><span>Data Absen</span></a>
                <a href="{{ route('rekap.admin') }}" class="nav-link" data-tooltip="Rekap Absen"><i class="fa-solid fa-chart-column"></i><span>Rekap Absen</span></a>
                <a href="{{ route('users.index') }}" class="nav-link" data-tooltip="Menu User"><i class="fa-solid fa-users"></i><span>Menu User</span></a>
                <a href="{{ route('lokasi-kantor.index') }}" class="nav-link" data-tooltip="Pengaturan Lokasi Kantor"><i class="fa-solid fa-location-dot"></i><span>Pengaturan Lokasi</span></a>
                @endif
            </nav>
            <div class="sidebar-logout">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-logout-btn" data-tooltip="Logout">
                        <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <div class="main-wrapper">
            <header class="navbar-app">
                <div class="navbar-left">
                    <button class="icon-btn toggle-sidebar" id="toggleSidebar" aria-label="Toggle sidebar"><i class="fa-solid fa-bars"></i></button>
                    <div class="navbar-title"><span class="nb-title-main">Sistem Absensi PKL</span><span class="nb-title-sub">PT Sandya Network</span></div>
                </div>
                <div class="navbar-right">
                    <div class="profile-area">
                        <div class="avatar"><img src="https://i.pravatar.cc/100?img=12" alt="Foto Profil"></div>
                        <div class="profile-meta"><span class="profile-name">{{ $userSession['username'] ?? 'User' }}</span><span class="profile-role">{{ ($userSession['role'] ?? 'siswa') === 'admin' ? 'Administrator' : 'Siswa PKL' }}</span></div>
                        <button class="icon-btn" id="profileDropdownBtn" aria-label="Dropdown profil"><i class="fa-solid fa-chevron-down"></i></button>
                        <div class="dropdown-menu" id="profileDropdown">
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item dropdown-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</button></form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="page-content">
                <div class="page-title" style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                    <a href="{{ route('laporan.sales.index') }}" class="btn-tool" style="text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Kembali ke Sales</a>
                    <div style="flex:1; min-width:200px;">
                        <h1 class="page-title-main" style="margin-bottom:2px;">Edit Laporan Sales #{{ $laporan->id }}</h1>
                        <p class="page-title-sub">Perbarui teks laporan utama. Data collect customer tidak berubah.</p>
                    </div>
                </div>

                @if(session('sales_success'))
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('sales_success') }}</div>
                @endif
                @if(session('sales_error'))
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('sales_error') }}</div>
                @endif
                @if(session('laporan_success'))
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('laporan_success') }}</div>
                @endif
                @if(session('laporan_error'))
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('laporan_error') }}</div>
                @endif
                @if(isset($errors) && $errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin:0; padding-left:18px;">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="laporan-form-card">
                    <h3><i class="fa-solid fa-pen-to-square"></i> Edit Laporan Sales</h3>

                    <div class="identitas-box">
                        <div class="ident-item"><span class="label">Nama Siswa</span><span class="value">{{ $laporan->user?->nama ?? $laporan->user?->username ?? '-' }}</span></div>
                        <div class="ident-item"><span class="label">Tanggal Laporan</span><span class="value">{{ $laporan->tanggal_laporan ? \Carbon\Carbon::parse($laporan->tanggal_laporan)->translatedFormat('d F Y') : '-' }} <span style="font-weight:400; color:#6B7280; font-size:0.78rem;">(tidak dapat diubah)</span></span></div>
                        <div class="ident-item"><span class="label">Data Customer</span><span class="value">{{ $laporan->sales ? $laporan->sales->count() : 0 }} data <span style="font-weight:400; color:#6B7280; font-size:0.78rem;">(tetap terhubung)</span></span></div>
                    </div>

                    @if($laporan->fotos && $laporan->fotos->isNotEmpty())
                        <div style="margin-bottom:16px;">
                            <div style="font-size:0.78rem; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Foto Saat Ini ({{ $laporan->fotos->count() }}/6 — tetap tersimpan)</div>
                            <div class="foto-lama-grid">
                                @foreach($laporan->fotos as $f)
                                    <img src="{{ route('laporan.foto.show', [$laporan->id, $f->id]) }}" alt="Foto {{ $loop->iteration }}" loading="lazy">
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('laporan.sales.update', $laporan->id) }}" enctype="multipart/form-data" id="salesEditForm">
                        @csrf
                        @method('PUT')
                        <div class="form-group" style="margin-bottom:14px;">
                            <label class="form-label" for="teks_laporan">Teks Laporan <span style="color:#DC2626;">*</span></label>
                            <textarea id="teks_laporan" name="teks_laporan" class="textarea-laporan" rows="6" placeholder="Tuliskan kegiatan yang dilakukan hari ini..." required>{{ old('teks_laporan', $laporan->teks_laporan) }}</textarea>
                        </div>

                        <div class="form-group" style="margin-bottom:16px;">
                            <label class="form-label" for="fotos">Tambah Foto <span style="font-weight:400; color:#6B7280; font-size:0.78rem;">(opsional, 0–6 foto baru, total maks 6, maks 10MB per foto)</span></label>
                            <div class="foto-input-wrap">
                                <input type="file" id="fotos" name="fotos[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="width:100%; font-size:0.88rem;" multiple>
                                <div id="fotoError" style="font-size:0.75rem; color:#DC2626; margin-top:6px; display:none;"></div>
                            </div>
                            <small style="font-size:0.72rem; color:#6B7280; margin-top:6px; display:block;">Kosongkan jika tidak ingin menambah foto. Foto yang sudah ada dan data customer tidak akan berubah.</small>
                        </div>

                        <div class="form-actions" style="display:flex; gap:10px; justify-content:flex-start; border-top:none; padding-top:0; margin-top:8px; flex-wrap:wrap;">
                            <button type="submit" class="btn-modal-submit" style="background:#0F4C81; color:#fff; border:1px solid #0F4C81; border-radius:10px; padding:10px 18px; font-size:0.88rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px;"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
                            <a href="{{ route('laporan.sales.index') }}" class="btn-modal-cancel" style="background:#fff; color:#374151; border:1px solid #E5E7EB; border-radius:10px; padding:10px 18px; font-size:0.88rem; font-weight:500; cursor:pointer; display:inline-flex; align-items:center; gap:8px; text-decoration:none;"><i class="fa-solid fa-xmark"></i> Batal</a>
                        </div>
                    </form>
                </div>

                <footer class="footer"><span>© 2026 PT Sandya Network</span><span class="footer-sep">•</span><span>Sistem Absensi PKL</span></footer>
            </main>
        </div>
    </div>

    <script src="{{ asset('js/dashboard.js') }}?v={{ @filemtime(public_path('js/dashboard.js')) }}"></script>
    <script>
        const fotoInput = document.getElementById('fotos');
        const fotoError = document.getElementById('fotoError');
        const editForm = document.getElementById('salesEditForm');
        if (fotoInput) {
            fotoInput.addEventListener('change', function(e){
                const files = Array.from(e.target.files);
                if (fotoError) { fotoError.style.display='none'; fotoError.textContent=''; }
                if (files.length > 6) {
                    if (fotoError) { fotoError.textContent = 'Maksimal 6 foto baru. Anda memilih ' + files.length + ' foto.'; fotoError.style.display='block'; }
                    fotoInput.value='';
                    return;
                }
                const allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
                for (const file of files) {
                    if (!allowed.includes(file.type)) {
                        const ext = file.name.split('.').pop().toLowerCase();
                        if (!['jpg','jpeg','png','webp'].includes(ext)) {
                            if (fotoError) { fotoError.textContent = 'Format foto harus JPG, JPEG, PNG, atau WEBP. File ' + file.name + ' tidak valid.'; fotoError.style.display='block'; }
                            fotoInput.value='';
                            return;
                        }
                    }
                    if (file.size > 10*1024*1024) {
                        if (fotoError) { fotoError.textContent = 'Ukuran foto terlalu besar. Maksimal 10 MB. File ' + file.name + ' terlalu besar.'; fotoError.style.display='block'; }
                        fotoInput.value='';
                        return;
                    }
                }
            });
        }
        if (editForm) {
            editForm.addEventListener('submit', function(e){
                if (fotoInput && fotoInput.files.length > 6) {
                    e.preventDefault();
                    if (fotoError){ fotoError.textContent='Maksimal 6 foto baru.'; fotoError.style.display='block'; }
                }
            });
        }
    </script>
</body>
</html>
