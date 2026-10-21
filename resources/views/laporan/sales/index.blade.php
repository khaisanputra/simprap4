<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Sales - Sistem Absensi PKL Sandya Network</title>
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
        .foto-preview { margin-top:10px; display:none; text-align:center; }
        .foto-preview img { max-width:100%; max-height:260px; border-radius:10px; border:1px solid #E5E7EB; object-fit:contain; background:#fff; }
        .laporan-isi-cell { font-size:0.85rem; color:#374151; line-height:1.6; max-width:380px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .laporan-table { min-width:780px; }
        .laporan-table th:nth-child(5) { min-width:280px; }
        .btn-sm { padding:7px 12px; border-radius:9px; font-size:0.78rem; font-weight:500; border:1px solid #E5E7EB; background:#fff; color:#374151; display:inline-flex; align-items:center; gap:6px; text-decoration:none; cursor:pointer; transition:all .2s; }
        .btn-sm:hover { border-color:#3B82F6; color:#3B82F6; background:#EFF6FF; }
        .btn-sm.excel:hover { border-color:#16A34A; color:#16A34A; background:#F0FDF4; }
        @media(max-width:640px){ .laporan-form-card{padding:16px;} }
        .filter-bar { display:flex; flex-wrap:wrap; gap:10px; align-items:end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:14px; margin-bottom:16px; }
        .filter-bar .form-group { flex:1; min-width:160px; display:flex; flex-direction:column; gap:5px; }
        .filter-bar .form-label { font-size:0.72rem; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:0.04em; }
        .filter-bar .form-control { height:38px; }
        .empty-state { text-align:center; padding:36px 20px; background:#fff; border:1px solid #E5E7EB; border-radius:16px; color:#6B7280; }
        .empty-state i { font-size:2rem; color:#CBD5E1; margin-bottom:10px; display:block; }
        .admin-foto-thumb { width:52px; height:52px; object-fit:cover; border-radius:10px; border:1px solid #E5E7EB; display:block; }
        .admin-laporan-table { min-width:760px; }
        .admin-laporan-table th:first-child, .admin-laporan-table td:first-child { text-align:center; }
        .laporan-tabs { display:flex; gap:8px; margin-bottom:18px; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:6px; width:fit-content; }
        .laporan-tab { padding:9px 18px; border-radius:10px; font-size:0.85rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:all .2s; border:1px solid transparent; color:#6B7280; background:transparent; }
        .laporan-tab.active { background:#0F4C81; color:#fff; border-color:#0F4C81; box-shadow:0 4px 12px rgba(15,76,129,0.2); }
        .laporan-tab:not(.active):hover { background:#F8FAFC; color:#0F4C81; border-color:#E5E7EB; }
        @media(max-width:640px){ .laporan-tabs{width:100%;} .laporan-tab{flex:1; justify-content:center;} }
        .jam-info { display:flex; align-items:center; gap:8px; font-size:0.78rem; padding:10px 12px; border-radius:10px; margin-bottom:14px; }
        .jam-info.open { background:#F0FDF4; color:#166534; border:1px solid #BBF7D0; }
        .jam-info.closed { background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; }
        .btn-tool { padding:7px 12px; border-radius:9px; font-size:0.78rem; font-weight:500; border:1px solid #E5E7EB; background:#fff; color:#374151; display:inline-flex; align-items:center; gap:6px; text-decoration:none; cursor:pointer; transition:all .2s; }
        .btn-tool:hover { border-color:#3B82F6; color:#3B82F6; background:#EFF6FF; }
        .btn-tool.primary { background:#0F4C81; color:#fff; border-color:#0F4C81; }
        .btn-tool.primary:hover { background:#0d3f6d; color:#fff; }
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
                    @if(auth()->check() && auth()->user()->role === 'admin')

                                        <div class="notif-wrap">
<button class="icon-btn has-badge" id="notifBtn" aria-label="Notifikasi"><i class="fa-regular fa-bell"></i><span class="badge-dot"></span></button>
                    </div>

                    @endif
                    <div class="profile-area">
                        <div class="avatar"><img src="https://i.pravatar.cc/100?img=12" alt="Foto Profil"></div>
                        <div class="profile-meta"><span class="profile-name">{{ $userSession['username'] ?? 'User' }}</span><span class="profile-role">{{ ($userSession['role'] ?? 'siswa') === 'admin' ? 'Administrator' : 'Siswa PKL' }}</span></div>
                        <button class="icon-btn" id="profileDropdownBtn" aria-label="Dropdown profil"><i class="fa-solid fa-chevron-down"></i></button>
                        <div class="dropdown-menu" id="profileDropdown">
                            @if (($userSession['role'] ?? 'siswa') === 'admin')
                            <a href="{{ route('users.index') }}" class="dropdown-item"><i class="fa-solid fa-users"></i> Menu User</a>
                            <div class="dropdown-divider"></div>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item dropdown-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</button></form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="page-content">
                <div class="page-title">
                    <h1 class="page-title-main">Laporan @if($isAdmin ?? false) — Data Siswa @endif</h1>
                    @if($isAdmin ?? false)
                        <p class="page-title-sub">Mode Admin — pantau laporan Sales siswa. Data Sales tersedia di halaman Data Sales.</p>
                    @else
                        <p class="page-title-sub">Buat laporan kegiatan harian Anda. Data siswa otomatis terisi dari akun login.</p>
                    @endif
                </div>

                <div class="laporan-tabs">
                    <a href="{{ route('laporan.index') }}" class="laporan-tab"><i class="fa-solid fa-screwdriver-wrench"></i> Teknisi</a>
                    <a href="{{ route('laporan.sales.index') }}" class="laporan-tab active"><i class="fa-solid fa-handshake"></i> Sales</a>
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

                @if (!($isAdmin ?? false))
                @if(isset($laporanHariIni) && $laporanHariIni)
                <!-- LAPORAN HARI INI SUDAH ADA — 1 laporan utama per hari (teknisi/sales) -->
                <div class="laporan-form-card" style="background:#F0FDF4; border-color:#BBF7D0;">
                    <h3><i class="fa-solid fa-circle-check" style="color:#16A34A;"></i> Laporan Hari Ini Sudah Dibuat</h3>
                    <p style="font-size:0.88rem; color:#374151; margin:0 0 12px;">Laporan hari ini sudah dibuat. Silakan edit laporan yang tersedia.</p>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <a href="{{ route('laporan.show', $laporanHariIni->id) }}" class="btn-tool" style="text-decoration:none;"><i class="fa-solid fa-eye"></i> Lihat Laporan Hari Ini</a>
                        @if(($laporanHariIni->jenis_laporan ?? null) === 'sales')
                        <a href="{{ route('laporan.sales.edit', $laporanHariIni->id) }}" class="btn-tool primary" style="text-decoration:none;"><i class="fa-solid fa-pen-to-square"></i> Edit Laporan Sales</a>
                        @else
                        <a href="{{ route('laporan.edit', $laporanHariIni->id) }}" class="btn-tool primary" style="text-decoration:none;"><i class="fa-solid fa-pen-to-square"></i> Edit Laporan Teknisi</a>
                        @endif
                    </div>
                </div>
                @else
                <!-- FORM INPUT - HANYA SISWA — SAMA PERSIS DENGAN TEKNISI -->
                <div class="laporan-form-card">
                    <h3><i class="fa-solid fa-pen-to-square"></i> Buat Laporan Sales</h3>

                    <!-- Identitas otomatis -->
                    <div class="identitas-box">
                        <div class="ident-item"><span class="label">Nama Siswa</span><span class="value">{{ $identitas['nama'] ?? '-' }}</span></div>
                        <div class="ident-item"><span class="label">USERNAME</span><span class="value">{{ $identitas['username'] ?? '-' }}</span></div>
                        @if(!empty($identitas['kelas']) || !empty($identitas['jurusan']))
                            <div class="ident-item"><span class="label">Kelas / Jurusan</span><span class="value">{{ trim(($identitas['kelas'] ?? '').' / '.($identitas['jurusan'] ?? ''), ' /') ?: '-' }}</span></div>
                        @endif
                        <div class="ident-item"><span class="label">Tanggal Laporan</span><span class="value">{{ $identitas['tanggal_hari_ini'] }} <span style="font-weight:400; color:#6B7280; font-size:0.78rem;">(otomatis hari ini)</span></span></div>
                    </div>

                    @if(isset($jamDibuka))
                        @if($jamDibuka)
                            <div class="jam-info open"><i class="fa-solid fa-circle-check"></i> Jam laporan dibuka: {{ $jamLaporan['mulai'] }} - {{ $jamLaporan['selesai'] }} WIB. Anda dapat mengirim laporan sekarang.</div>
                        @else
                            <div class="jam-info closed"><i class="fa-solid fa-clock"></i> Laporan Sales hanya dapat dibuat pada jam {{ $jamLaporan['mulai'] }} - {{ $jamLaporan['selesai'] }} WIB. Di luar jam tersebut laporan ditolak.</div>
                        @endif
                    @endif

                    <form method="POST" action="{{ route('laporan.sales.store') }}" enctype="multipart/form-data" id="laporanForm">
                        @csrf
                        <div class="form-group" style="margin-bottom:14px;">
                            <label class="form-label" for="teks_laporan">Teks Laporan <span style="color:#DC2626;">*</span></label>
                            <textarea id="teks_laporan" name="teks_laporan" class="textarea-laporan" rows="6" placeholder="Tuliskan kegiatan yang dilakukan hari ini..." required>{{ old('teks_laporan') }}</textarea>
                        </div>

                        <div class="form-group" style="margin-bottom:16px;">
                            <label class="form-label" for="fotos">Foto Dokumentasi <span style="color:#DC2626;">*</span> <span style="font-weight:400; color:#6B7280; font-size:0.78rem;">(1–6 foto, maks 10MB per foto, otomatis dikompres)</span></label>
                            <div class="foto-input-wrap">
                                <input type="file" id="fotos" name="fotos[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="width:100%; font-size:0.88rem;" required multiple>
                                <div class="foto-preview" id="fotoPreview" style="display:none; text-align:center;">
                                    <div id="fotoPreviewGrid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(90px,1fr)); gap:8px; margin-bottom:8px;"></div>
                                    <button type="button" class="btn-sm" onclick="clearFoto()"><i class="fa-solid fa-xmark"></i> Hapus Semua Foto</button>
                                    <div id="fotoInfo" style="font-size:0.75rem; color:#6B7280; margin-top:6px;"></div>
                                    <div id="fotoError" style="font-size:0.75rem; color:#DC2626; margin-top:6px; display:none;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions" style="display:flex; gap:10px; justify-content:flex-start; border-top:none; padding-top:0; margin-top:8px;">
                            <button type="submit" class="btn-modal-submit" style="background:#0F4C81; color:#fff; border:1px solid #0F4C81; border-radius:10px; padding:10px 18px; font-size:0.88rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px;"><i class="fa-solid fa-floppy-disk"></i> Simpan Laporan</button>
                            <button type="reset" class="btn-modal-cancel" onclick="clearFoto(); document.getElementById('teks_laporan').value='';" style="background:#fff; color:#374151; border:1px solid #E5E7EB; border-radius:10px; padding:10px 18px; font-size:0.88rem; font-weight:500; cursor:pointer; display:inline-flex; align-items:center; gap:8px;"><i class="fa-solid fa-rotate-left"></i> Batal</button>
                        </div>
                        <small style="font-size:0.72rem; color:#6B7280; margin-top:8px; display:block;">Setelah laporan utama disimpan, Anda akan diarahkan ke halaman Collect Data Sales untuk mengisi 1–10 data customer.</small>
                    </form>
                </div>
                @endif

                <div style="display:flex; gap:10px; margin-bottom:22px;">
                    <a href="{{ route('laporan.sales.data') }}" class="btn-tool primary" style="text-decoration:none;"><i class="fa-solid fa-table"></i> Lihat Data Sales</a>
                </div>

                <!-- LAPORAN SALES SAYA — daftar laporan utama dari laporans (jenis=sales, milik sendiri) -->
                @if(isset($laporanSalesSaya) && $laporanSalesSaya->count() > 0)
                <div class="card-table">
                    <div class="card-table-header">
                        <div class="ct-title">
                            <i class="fa-solid fa-table"></i>
                            <div>
                                <h3>Laporan Sales Saya</h3>
                                <p>Daftar laporan Sales milik Anda dari tabel laporans — Data Sales menampilkan hasil customer dari laporan_sales.</p>
                            </div>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="activity-table laporan-table">
                            <thead>
                                <tr>
                                    <th style="width:55px">No</th>
                                    <th style="width:130px">Tanggal</th>
                                    <th>Ringkasan Laporan</th>
                                    <th style="width:90px; text-align:center;">Foto</th>
                                    <th style="width:130px; text-align:center;">Status</th>
                                    <th style="width:200px">Collect Data</th>
                                    <th style="width:230px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($laporanSalesSaya as $ls)
                                    @php
                                        $isPaginated = method_exists($laporanSalesSaya, 'currentPage');
                                        $nomorLs = $isPaginated
                                            ? ($laporanSalesSaya->currentPage() - 1) * $laporanSalesSaya->perPage() + $loop->iteration
                                            : $loop->iteration;
                                        $tglLs = $ls->tanggal_laporan ? \Carbon\Carbon::parse($ls->tanggal_laporan)->format('d-m-Y') : \Carbon\Carbon::parse($ls->created_at)->format('d-m-Y');
                                        $ringkasLs = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', trim($ls->teks_laporan ?? '')), 80);
                                        $fotoCountLs = $ls->fotos ? $ls->fotos->count() : 0;
                                        $collectCountLs = $ls->sales_count ?? ($ls->sales ? $ls->sales->count() : 0);
                                        $sudahCollect = (int) $collectCountLs > 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $nomorLs }}</td>
                                        <td style="white-space:nowrap;">{{ $tglLs }}</td>
                                        <td><div class="laporan-isi-cell" title="{{ $ls->teks_laporan }}">{{ $ringkasLs }}</div></td>
                                        <td style="text-align:center; white-space:nowrap;">{{ $fotoCountLs }} foto</td>
                                        <td style="text-align:center;">
                                            @if($sudahCollect)
                                                <span style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:0.73rem; font-weight:600; background:#F0FDF4; color:#166534; border:1px solid #BBF7D0; white-space:nowrap;"><i class="fa-solid fa-circle-check"></i> Sudah Collect ({{ $collectCountLs }})</span>
                                            @else
                                                <span style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:0.73rem; font-weight:600; background:#FFFBEB; color:#92400E; border:1px solid #FDE68A; white-space:nowrap;"><i class="fa-solid fa-clock"></i> Belum Collect</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{-- Collect Data = proses lanjutan, TERPISAH dari Aksi laporan --}}
                                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                                @if(!$sudahCollect)
                                                    <a href="{{ route('laporan.sales.collect', $ls->id) }}" class="btn-tool primary" style="text-decoration:none; white-space:nowrap;"><i class="fa-solid fa-handshake"></i> Lanjutkan Collect Data</a>
                                                @else
                                                    <a href="{{ route('laporan.sales.data') }}" class="btn-tool" style="text-decoration:none; white-space:nowrap;"><i class="fa-solid fa-table"></i> Lihat Data</a>
                                                    <a href="{{ route('laporan.sales.collect', $ls->id) }}" class="btn-tool" style="text-decoration:none; white-space:nowrap;" title="Tambah data customer (maks total 10 per laporan)"><i class="fa-solid fa-plus"></i> Tambah</a>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            {{-- Aksi laporan = Detail | Edit | Print | Hapus --}}
                                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                                <a href="{{ route('laporan.show', $ls->id) }}" class="btn-tool" style="text-decoration:none; white-space:nowrap;"><i class="fa-solid fa-eye"></i> Detail</a>
                                                <a href="{{ route('laporan.sales.edit', $ls->id) }}" class="btn-tool" style="text-decoration:none; white-space:nowrap;"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                                <a href="{{ route('laporan.print', $ls->id) }}" target="_blank" class="btn-tool" style="text-decoration:none; white-space:nowrap;"><i class="fa-solid fa-print"></i> Print</a>
                                                <form method="POST" action="{{ route('laporan.destroy', $ls->id) }}" onsubmit="return confirm('Hapus laporan Sales ini? Foto dan data collect terkait akan ikut terhapus.')" style="display:inline; margin:0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-tool" style="color:#DC2626; border-color:#FECACA; white-space:nowrap;"><i class="fa-solid fa-trash"></i> Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer">
                        @php $totalLs = method_exists($laporanSalesSaya, 'total') ? $laporanSalesSaya->total() : $laporanSalesSaya->count(); @endphp
                        <span>Menampilkan <strong>{{ $totalLs }}</strong> laporan Sales saya</span>
                        @if(method_exists($laporanSalesSaya, 'links'))
                        <div class="pagination-links">{{ $laporanSalesSaya->links('vendor.pagination.custom') }}</div>
                        @endif
                    </div>
                </div>
                @else
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <p style="font-weight:600; color:#374151;">Belum ada Laporan Sales Saya</p>
                        <p style="font-size:0.85rem; margin-top:4px;">Buat laporan Sales pertama Anda menggunakan form di atas. Laporan yang belum Collect akan muncul di sini dengan status Belum Collect.</p>
                    </div>
                @endif
                @else
                <div class="laporan-form-card" style="background:#F8FAFC; border-style:dashed;">
                    <h3><i class="fa-solid fa-eye"></i> Mode Admin — Lihat Data Sales</h3>
                    <p style="font-size:0.88rem; color:#6B7280; margin:0 0 12px;">Admin tidak dapat membuat laporan Sales. Gunakan halaman Data Sales untuk memantau laporan siswa dan export Excel.</p>
                    <a href="{{ route('laporan.sales.data') }}" class="btn-tool primary" style="text-decoration:none;"><i class="fa-solid fa-table"></i> Lihat hasil collect data</a>
                </div>
                @endif

                <footer class="footer"><span>© 2026 PT Sandya Network</span><span class="footer-sep">•</span><span>Sistem Absensi PKL</span></footer>
            </main>
        </div>
    </div>

    <script src="{{ asset('js/dashboard.js') }}?v={{ @filemtime(public_path('js/dashboard.js')) }}"></script>
    @if(auth()->check() && auth()->user()->role === 'admin')
    <script src="{{ asset('js/admin-notifikasi.js') }}"></script>
    @endif
    <script src="{{ asset('js/foto-helper.js') }}?v={{ @filemtime(public_path('js/foto-helper.js')) }}"></script>
    <script>
        // Preview + resize 1600px JPEG 0.82 sebelum upload (shared FotoHelper) — SAMA DENGAN TEKNISI
        const fotoInput = document.getElementById('fotos');
        const previewWrap = document.getElementById('fotoPreview');
        const previewGrid = document.getElementById('fotoPreviewGrid');
        const fotoInfo = document.getElementById('fotoInfo');
        const fotoError = document.getElementById('fotoError');
        const laporanForm = document.getElementById('laporanForm');
        let fotosProcessing = false;

        if (fotoInput) {
            fotoInput.addEventListener('change', function(e){
                const files = Array.from(e.target.files);
                if (previewGrid) previewGrid.innerHTML = '';
                if (fotoError) { fotoError.style.display='none'; fotoError.textContent=''; }
                if (files.length === 0) { if(previewWrap) previewWrap.style.display='none'; if(fotoInfo) fotoInfo.textContent=''; return; }
                if (files.length > 6) {
                    if (fotoError) { fotoError.textContent = 'Maksimal 6 foto dapat diunggah untuk satu laporan. Anda memilih ' + files.length + ' foto. Silakan pilih ulang maksimal 6.'; fotoError.style.display='block'; }
                    fotoInput.value=''; if(previewGrid) previewGrid.innerHTML=''; if(previewWrap) previewWrap.style.display='none'; return;
                }
                if (files.length < 1) {
                    if (fotoError) { fotoError.textContent = 'Minimal 1 foto wajib dipilih.'; fotoError.style.display='block'; }
                    return;
                }
                const allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
                for (const file of files) {
                    if (!allowed.includes(file.type)) {
                        const ext = file.name.split('.').pop().toLowerCase();
                        if (!['jpg','jpeg','png','webp'].includes(ext)) {
                            if (fotoError) { fotoError.textContent = 'Format foto harus JPG, JPEG, PNG, atau WEBP. File ' + file.name + ' tidak valid.'; fotoError.style.display='block'; }
                            fotoInput.value=''; if(previewGrid) previewGrid.innerHTML=''; if(previewWrap) previewWrap.style.display='none'; return;
                        }
                    }
                    if (file.size > 10*1024*1024) {
                        if (fotoError) { fotoError.textContent = 'Ukuran foto terlalu besar. Maksimal ukuran foto adalah 10 MB. File ' + file.name + ' terlalu besar.'; fotoError.style.display='block'; }
                        fotoInput.value=''; if(previewGrid) previewGrid.innerHTML=''; if(previewWrap) previewWrap.style.display='none'; return;
                    }
                }
                if (fotoInfo) fotoInfo.textContent = 'Memproses ' + files.length + ' foto (resize 1600px JPEG 82%)...';
                fotosProcessing = true;
                if (laporanForm) { const b = laporanForm.querySelector('button[type=submit]'); if(b) b.disabled=true; }
                const helper = window.FotoHelper;
                function showPreviewAndInject(newFiles){
                    const ok = helper && helper.injectFilesToInput ? helper.injectFilesToInput(fotoInput, newFiles) : false;
                    if (!ok) {
                        if (fotoError){ fotoError.textContent='Gagal menyiapkan foto terkompresi (inject gagal). Silakan pilih ulang foto.'; fotoError.style.display='block'; }
                        if (fotoInfo) fotoInfo.textContent='';
                        fotoInput.value='';
                        if (previewGrid) previewGrid.innerHTML='';
                        if (previewWrap) previewWrap.style.display='none';
                        fotosProcessing=false;
                        if (laporanForm){ const b=laporanForm.querySelector('button[type=submit]'); if(b) b.disabled=false; }
                        return;
                    }
                    if (previewGrid) previewGrid.innerHTML='';
                    newFiles.forEach(function(file){
                        try {
                            const url = URL.createObjectURL(file);
                            const div = document.createElement('div');
                            div.style.cssText = 'border:1px solid #E5E7EB; border-radius:8px; overflow:hidden; background:#fff; padding:4px;';
                            div.innerHTML = '<img src="'+url+'" style="width:100%; height:80px; object-fit:cover; border-radius:6px; display:block;"><div style="font-size:0.68rem; color:#6B7280; margin-top:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">'+file.name+' ('+(file.size/1024).toFixed(0)+' KB)</div>';
                            if (previewGrid) previewGrid.appendChild(div);
                        } catch(err){}
                    });
                    if (previewWrap) previewWrap.style.display='block';
                    if (fotoInfo) fotoInfo.textContent = 'Siap kirim ' + newFiles.length + ' foto terkompresi — ' + newFiles.length + '/6 (JPEG 1600px)';
                    fotosProcessing = false;
                    if (laporanForm) { const b = laporanForm.querySelector('button[type=submit]'); if(b) b.disabled=false; }
                }
                if (helper && helper.compressMultipleFiles) {
                    helper.compressMultipleFiles(files, function(newFiles, err){
                        if (err || !newFiles) {
                            if (fotoError){ fotoError.textContent = err || 'Gagal memproses foto. Coba lagi.'; fotoError.style.display='block'; }
                            if (fotoInfo) fotoInfo.textContent='';
                            fotoInput.value='';
                            if (previewGrid) previewGrid.innerHTML='';
                            if (previewWrap) previewWrap.style.display='none';
                            fotosProcessing=false;
                            if (laporanForm){ const b=laporanForm.querySelector('button[type=submit]'); if(b) b.disabled=false; }
                            return;
                        }
                        showPreviewAndInject(newFiles);
                    });
                } else {
                    // Tanpa helper: jangan fallback ke file asli, tampilkan error
                    if (fotoError){ fotoError.textContent='Gagal memproses foto: helper tidak tersedia. Muat ulang halaman dan coba lagi.'; fotoError.style.display='block'; }
                    if (fotoInfo) fotoInfo.textContent='';
                    fotoInput.value='';
                    if (previewGrid) previewGrid.innerHTML='';
                    if (previewWrap) previewWrap.style.display='none';
                    fotosProcessing=false;
                    if (laporanForm){ const b=laporanForm.querySelector('button[type=submit]'); if(b) b.disabled=false; }
                    return;
                }
            });
        }
        function clearFoto(){
            if(fotoInput) fotoInput.value='';
            if(previewWrap) previewWrap.style.display='none';
            const grid = document.getElementById('fotoPreviewGrid');
            if(grid) grid.innerHTML='';
            if(fotoInfo) fotoInfo.textContent='';
            const err = document.getElementById('fotoError');
            if(err){ err.style.display='none'; err.textContent=''; }
            fotosProcessing=false;
            if(laporanForm){ const b=laporanForm.querySelector('button[type=submit]'); if(b) b.disabled=false; }
        }
        if (laporanForm) {
            laporanForm.addEventListener('submit', function(e){
                if (fotosProcessing) {
                    e.preventDefault();
                    if (fotoError){ fotoError.textContent='Foto masih diproses, tunggu sebentar...'; fotoError.style.display='block'; }
                    return;
                }
                if (!fotoInput.files || fotoInput.files.length===0) {
                    e.preventDefault();
                    if (fotoError){ fotoError.textContent='Minimal 1 foto wajib dipilih.'; fotoError.style.display='block'; }
                    return;
                }
                if (fotoInput.files.length>6) {
                    e.preventDefault();
                    if (fotoError){ fotoError.textContent='Maksimal 6 foto.'; fotoError.style.display='block'; }
                    return;
                }
            });
        }
    </script>
</body>
</html>
