<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan - {{ $laporan->user?->nama ?? 'Siswa' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    <style>
        .detail-doc { background:#fff; border:1px solid #E5E7EB; border-radius:18px; box-shadow:0 10px 30px rgba(15,76,129,0.08); overflow:hidden; max-width:860px; margin:0 auto; }
        .detail-header { display:flex; align-items:center; gap:14px; padding:20px 22px; border-bottom:1px solid #E5E7EB; background:linear-gradient(135deg,#F8FAFC 0%, #EFF6FF 100%); }
        .detail-header .logo { width:48px; height:48px; flex-shrink:0; }
        .detail-header .logo img { width:100%; height:auto; object-fit:contain; display:block; }
        .detail-header h2 { font-size:1.15rem; color:#0F4C81; margin:0; }
        .detail-header p { font-size:0.82rem; color:#6B7280; margin:2px 0 0; }
        .detail-body { padding:22px; }
        .section-title { font-size:0.82rem; font-weight:700; color:#0F4C81; text-transform:uppercase; letter-spacing:0.05em; border-left:3px solid #0F4C81; padding-left:10px; margin:18px 0 10px; }
        .section-title:first-child { margin-top:0; }
        .ident-grid { display:grid; grid-template-columns:160px 1fr; gap:8px 14px; background:#F8FAFC; border:1px solid #E5E7EB; border-radius:12px; padding:14px 16px; }
        .ident-label { font-size:0.82rem; color:#6B7280; font-weight:500; }
        .ident-value { font-size:0.88rem; color:#1F2937; font-weight:600; word-break:break-word; }
        .teks-box { background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px; font-size:0.92rem; line-height:1.8; white-space:pre-wrap; word-wrap:break-word; text-align:justify; }
        .foto-box { text-align:center; border:1px solid #E5E7EB; border-radius:12px; padding:14px; background:#F9FAFB; }
        .foto-box img { max-width:100%; height:auto; max-height:520px; border-radius:10px; border:1px solid #E5E7EB; object-fit:contain; background:#fff; }
        .foto-caption { font-size:0.78rem; color:#6B7280; margin-top:8px; font-style:italic; }
        .no-foto { color:#9CA3AF; font-style:italic; padding:18px; border:1px dashed #D1D5DB; border-radius:10px; background:#fff; }
        .action-bar { display:flex; flex-wrap:wrap; gap:10px; padding:16px 22px; border-top:1px solid #E5E7EB; background:#FBFDFF; justify-content:flex-end; }
        @media(max-width:640px){ .ident-grid{grid-template-columns:1fr; gap:4px;} .ident-label{font-size:0.78rem;} .detail-body{padding:16px;} }
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
                    <div class="navbar-title"><span class="nb-title-main">Detail Laporan</span><span class="nb-title-sub">PT Sandya Network</span></div>
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
                <div class="page-title" style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                    <a href="{{ route('laporan.index') }}" class="btn-tool" style="text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Kembali ke Laporan</a>
                    <div style="flex:1; min-width:200px;">
                        <h1 class="page-title-main" style="margin-bottom:2px;">Detail Laporan</h1>
                        <p class="page-title-sub">Urutan: Identitas → Teks Laporan → Foto Dokumentasi</p>
                    </div>
                </div>

                @if(session('laporan_success'))
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('laporan_success') }}</div>
                @endif
                @if(session('laporan_error'))
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('laporan_error') }}</div>
                @endif
                @if(session('sales_success'))
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('sales_success') }}</div>
                @endif
                @if(session('sales_error'))
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('sales_error') }}</div>
                @endif

                @php
                    $namaSiswa = $laporan->user?->nama ?? $laporan->user?->username ?? '-';
                    $nis = $laporan->user?->nis ?? $laporan->user?->nisn ?? null;
                    $kelas = $laporan->user?->kelas ?? $laporan->user?->kelas_jurusan ?? null;
                    $jurusan = $laporan->user?->jurusan ?? null;
                    $kelasJurusan = null;
                    if($kelas && $jurusan) $kelasJurusan = $kelas.' / '.$jurusan;
                    elseif($kelas) $kelasJurusan = $kelas;
                    elseif($jurusan) $kelasJurusan = $jurusan;
                    $tgl = $laporan->tanggal_laporan ? \Carbon\Carbon::parse($laporan->tanggal_laporan)->translatedFormat('d F Y') : '-';
                @endphp

                <div class="detail-doc">
                    <div class="detail-header">
                        <div class="logo"><img src="{{ asset('images/logo-sandya.png') }}" alt="Logo" onerror="this.style.display='none'"></div>
                        <div>
                            <h2>LAPORAN KEGIATAN SISWA PKL</h2>
                            <p>{{ $lokasi['nama'] ?? 'PT Sandya Network' }} — Sistem Absensi PKL</p>
                        </div>
                    </div>

                    <div class="detail-body">
                        <!-- IDENTITAS SISWA -->
                        <div class="section-title"><i class="fa-solid fa-id-card"></i> Identitas Siswa</div>
                        <div class="ident-grid">
                            <div class="ident-label">Nama Siswa</div><div class="ident-value">{{ $namaSiswa }}</div>
                            <div class="ident-label">USERNAME</div><div class="ident-value">{{ $laporan->user?->username ?? '-' }}</div>
                            @if($kelasJurusan)
                                <div class="ident-label">Kelas / Jurusan</div><div class="ident-value">{{ $kelasJurusan }}</div>
                            @endif
                            <div class="ident-label">Tanggal Laporan</div><div class="ident-value">{{ $tgl }}</div>
                            @if(!empty($laporan->user?->email))
                                <div class="ident-label">Email</div><div class="ident-value" style="font-weight:400; font-size:0.85rem;">{{ $laporan->user->email }}</div>
                            @endif
                        </div>

                        <!-- TEKS LAPORAN -->
                        <div class="section-title"><i class="fa-solid fa-file-lines"></i> Teks Laporan</div>
                        <div class="teks-box">{{ $laporan->teks_laporan }}</div>

                        <!-- FOTO DOKUMENTASI - wajib di bawah teks, maksimal 6 -->
                        <div class="section-title"><i class="fa-solid fa-image"></i> Foto Dokumentasi <span style="font-weight:400; font-size:0.78rem; color:#6B7280;">(maksimal 6 foto)</span></div>
                        @php $fotos = $laporan->fotos ?? collect(); @endphp
                        @if($fotos->isNotEmpty())
                            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:12px;">
                                @foreach($fotos as $f)
                                    <div class="foto-box" style="padding:10px;">
                                        <img src="{{ route('laporan.foto.show', [$laporan->id, $f->id]) }}" alt="Foto {{ $loop->iteration }}" loading="lazy" style="max-height:220px; width:100%; object-fit:cover; border-radius:8px; border:1px solid #E5E7EB;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
                                        <div style="display:none;" class="no-foto">Foto tidak dapat dimuat.</div>
                                        @if(!empty($f->foto_original_name))
                                            <div class="foto-caption">File: {{ $f->foto_original_name }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @elseif(!empty($laporan->foto))
                            <div class="foto-box">
                                <img src="{{ route('laporan.foto', $laporan->id) }}" alt="Foto Dokumentasi" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
                                <div style="display:none;" class="no-foto">Foto tidak dapat dimuat.</div>
                                @if(!empty($laporan->foto_original_name))
                                    <div class="foto-caption">File: {{ $laporan->foto_original_name }}</div>
                                @endif
                            </div>
                        @else
                            <div class="no-foto">Tidak ada foto dokumentasi untuk laporan ini.</div>
                        @endif
                    </div>

                    <div class="action-bar">
                        <a href="{{ route('laporan.print', $laporan->id) }}" target="_blank" class="btn-tool" style="text-decoration:none;"><i class="fa-solid fa-print"></i> Print</a>
                        @if(($userSession['role'] ?? '')==='admin' || (int)($laporan->user_id) === (int)($userSession['user_id'] ?? 0))
                        @if(($laporan->jenis_laporan ?? null) === 'sales')
                        <a href="{{ route('laporan.sales.edit', $laporan->id) }}" class="btn-tool" style="text-decoration:none;"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                        @else
                        <a href="{{ route('laporan.edit', $laporan->id) }}" class="btn-tool" style="text-decoration:none;"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                        @endif
                        <form method="POST" action="{{ route('laporan.destroy', $laporan->id) }}" onsubmit="return confirm('Hapus laporan ini?')" style="display:inline;">@csrf @method('DELETE')<button type="submit" class="btn-tool" style="color:#DC2626; border-color:#FECACA;"><i class="fa-solid fa-trash"></i> Hapus</button></form>
                        @endif
                    </div>
                </div>

                <footer class="footer"><span>© 2026 PT Sandya Network</span><span class="footer-sep">•</span><span>Sistem Absensi PKL</span></footer>
            </main>
        </div>
    </div>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    @if(auth()->check() && auth()->user()->role === 'admin')
    <script src="{{ asset('js/admin-notifikasi.js') }}"></script>
    @endif
</body>
</html>
