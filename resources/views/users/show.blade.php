<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail User - Sistem Absensi PKL Sandya Network</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
</head>
<body>
    <div class="app-shell">

        <!-- ============ SIDEBAR ============ -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-logo"><img src="{{ asset('images/logo-sandya2.png') }}" alt="Sandya Absensi PKL"></div>
                <div class="brand-text">
                    <span class="brand-name">Sandya</span>
                    <span class="brand-sub">Absensi PKL</span>
                </div>
                <button class="sidebar-close" id="sidebarClose" aria-label="Tutup menu"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <nav class="sidebar-nav">
                <p class="nav-section">Menu Utama</p>
                <a href="{{ route('dashboard') }}" class="nav-link" data-tooltip="Dashboard">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('absen.masuk') }}" class="nav-link" data-tooltip="Absen Masuk">
                    <i class="fa-solid fa-arrow-right-to-bracket" style="color:#10B981"></i>
                    <span>Absen Masuk</span>
                </a>
                <a href="{{ route('absen.pulang') }}" class="nav-link" data-tooltip="Absen Pulang">
                    <i class="fa-solid fa-arrow-right-from-bracket" style="color:#F87171"></i>
                    <span>Absen Pulang</span>
                </a>
                <a href="{{ route('laporan.index') }}" class="nav-link" data-tooltip="Laporan">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Laporan</span>
                </a>

                <p class="nav-section">Lainnya</p>
                <a href="{{ route('data-absen-masuk.index') }}" class="nav-link" data-tooltip="Data Absen">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Data Absen</span>
                </a>
                <a href="{{ route('rekap.admin') }}" class="nav-link" data-tooltip="Rekap Absen"><i class="fa-solid fa-chart-column"></i><span>Rekap Absen</span></a>
                <a href="{{ route('users.index') }}" class="nav-link active" data-tooltip="Menu User">
                    <i class="fa-solid fa-users"></i>
                    <span>Menu User</span>
                </a>
                <a href="{{ route('lokasi-kantor.index') }}" class="nav-link" data-tooltip="Pengaturan Lokasi Kantor">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Pengaturan Lokasi</span>
                </a>
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

        <!-- Overlay mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- ============ MAIN ============ -->
        <div class="main-wrapper">

            <!-- ============ NAVBAR ============ -->
            <header class="navbar-app">
                <div class="navbar-left">
                    <button class="icon-btn toggle-sidebar" id="toggleSidebar" aria-label="Toggle sidebar">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="navbar-title">
                        <span class="nb-title-main">Sistem Absensi PKL</span>
                        <span class="nb-title-sub">PT Sandya Network</span>
                    </div>
                </div>

                <div class="navbar-right">
                    @if(auth()->check() && auth()->user()->role === 'admin')

                                        <div class="notif-wrap">
<button class="icon-btn has-badge" id="notifBtn" aria-label="Notifikasi">
                        <i class="fa-regular fa-bell"></i>
                        <span class="badge-dot"></span>
                    </button>
                    </div>

                    @endif

                    <div class="profile-area">
                        <div class="avatar">
                            <img src="https://i.pravatar.cc/100?img=12" alt="Foto Profil Admin">
                        </div>
                        <div class="profile-meta">
                            <span class="profile-name">{{ $userSession['username'] ?? 'Admin' }}</span>
                            <span class="profile-role">Administrator</span>
                        </div>
                        <button class="icon-btn" id="profileDropdownBtn" aria-label="Dropdown profil">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>

                        <div class="dropdown-menu" id="profileDropdown">
                            <a href="{{ route('users.index') }}" class="dropdown-item"><i class="fa-solid fa-users"></i> Menu User</a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item dropdown-logout">
                                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ============ CONTENT ============ -->
            <main class="page-content">
                <div class="page-title" style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                    <a href="{{ route('users.index') }}" class="btn-tool" style="text-decoration:none;">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Menu User
                    </a>
                    <div style="flex:1; min-width:200px;">
                        <h1 class="page-title-main" style="margin-bottom:2px;">Detail User</h1>
                        <p class="page-title-sub">Informasi lengkap akun user yang terdaftar di sistem.</p>
                    </div>
                </div>

                @php
                    $namaUser = $user->nama ?: $user->username;
                    $dibuat = $user->created_at
                        ? \Carbon\Carbon::parse($user->created_at)->translatedFormat('d F Y, H:i')
                        : '-';
                    $diperbarui = $user->updated_at
                        ? \Carbon\Carbon::parse($user->updated_at)->translatedFormat('d F Y, H:i')
                        : '-';
                    $tanggalLahir = $user->tanggal_lahir ? $user->tanggal_lahir->translatedFormat('d F Y') : 'Belum diisi';
                    $pklMulai = $user->pkl_mulai ? $user->pkl_mulai->translatedFormat('d F Y') : 'Belum diisi';
                    $pklSelesai = $user->pkl_selesai ? $user->pkl_selesai->translatedFormat('d F Y') : 'Belum diisi';
                @endphp

                <div class="card-table">
                    <div class="card-table-header">
                        <div class="ct-title">
                            <div>
                                <h3>Informasi User</h3>
                                <p>Data akun <strong>{{ $namaUser }}</strong></p>
                            </div>
                        </div>
                    </div>

                    <div style="padding:22px;">
                        <div class="detail-hero">
                            <div class="detail-hero-info">
                                <span class="detail-role-label">{{ $user->role === 'admin' ? 'Administrator' : 'Siswa PKL' }}</span>
                                <h4>{{ $namaUser }}</h4>
                                <div class="detail-meta">
                                    <span><i class="fa-solid fa-at"></i> {{ $user->username }}</span>
                                    <span><i class="fa-solid fa-envelope"></i> {{ $user->email ?: '-' }}</span>
                                    <span><i class="fa-solid fa-id-badge"></i> {{ $user->user_id ?: '-' }}</span>
                                </div>
                            </div>
                            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px; margin-left:auto;">
                                <span class="badge-status {{ $user->role === 'admin' ? 'badge-admin' : 'badge-siswa' }}">
                                    {{ $user->role === 'admin' ? 'Admin' : 'Siswa' }}
                                </span>
                                <span class="badge-status {{ $user->status === 'Aktif' ? 'badge-masuk' : 'badge-nonaktif' }}">
                                    {{ $user->status ?: '-' }}
                                </span>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h5><i class="fa-solid fa-id-card"></i> Identitas Akun</h5>
                            <div class="detail-grid">
                                <div class="detail-row">
                                    <span class="detail-label">Nama</span>
                                    <span class="detail-value">{{ $user->nama ?: '-' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Username</span>
                                    <span class="detail-value">{{ $user->username }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value">{{ $user->email ?: '-' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">User ID</span>
                                    <span class="detail-value">{{ $user->user_id ?: '-' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Role</span>
                                    <span class="detail-value">{{ $user->role === 'admin' ? 'Admin' : 'Siswa' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value">{{ $user->status ?: '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h5><i class="fa-solid fa-address-card"></i> Identitas Pribadi</h5>
                            <div class="detail-grid">
                                <div class="detail-row">
                                    <span class="detail-label">NIK</span>
                                    <span class="detail-value" style="font-family:monospace;">{{ $user->nik ?: 'Belum diisi' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Tempat Lahir</span>
                                    <span class="detail-value">{{ $user->tempat_lahir ?: 'Belum diisi' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Tanggal Lahir</span>
                                    <span class="detail-value">{{ $tanggalLahir }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Nomor Telepon</span>
                                    <span class="detail-value">{{ $user->no_telepon ?: 'Belum diisi' }}</span>
                                </div>
                                <div class="detail-row" style="grid-column: 1 / -1;">
                                    <span class="detail-label">Alamat Lengkap</span>
                                    <span class="detail-value">{{ $user->alamat ?: 'Belum diisi' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h5><i class="fa-solid fa-school"></i> Informasi Sekolah</h5>
                            <div class="detail-grid">
                                <div class="detail-row">
                                    <span class="detail-label">Asal Sekolah</span>
                                    <span class="detail-value">{{ $user->asal_sekolah ?: 'Belum diisi' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Jenis Identitas Sekolah</span>
                                    <span class="detail-value">{{ $user->jenis_identitas_sekolah ?: 'Belum diisi' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">{{ $user->jenis_identitas_sekolah ?: 'Nomor Identitas Sekolah' }}</span>
                                    <span class="detail-value" style="font-family:monospace;">{{ $user->nomor_identitas_sekolah ?: 'Belum diisi' }}</span>
                                </div>
                                <div class="detail-row" style="grid-column: 1 / -1;">
                                    <span class="detail-label">Alamat Sekolah</span>
                                    <span class="detail-value">{{ $user->alamat_sekolah ?: 'Belum diisi' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h5><i class="fa-solid fa-calendar-days"></i> Periode PKL</h5>
                            <div class="detail-grid">
                                <div class="detail-row">
                                    <span class="detail-label">Tanggal Mulai PKL</span>
                                    <span class="detail-value">{{ $pklMulai }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Tanggal Selesai PKL</span>
                                    <span class="detail-value">{{ $pklSelesai }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h5><i class="fa-solid fa-folder-open"></i> Dokumen Identitas</h5>
                            <div class="detail-grid">
                                <div class="detail-row" style="grid-column: 1 / -1;">
                                    <span class="detail-label">KTP</span>
                                    <span class="detail-value">
                                        @if($user->ktp_path)
                                            <a href="{{ route('users.document', [$user, 'ktp']) }}" target="_blank" rel="noopener" class="btn-tool" style="text-decoration:none;"><i class="fa-solid fa-eye"></i> Lihat KTP</a>
                                        @else
                                            Belum diunggah
                                        @endif
                                    </span>
                                </div>
                                <div class="detail-row" style="grid-column: 1 / -1;">
                                    <span class="detail-label">Kartu Pelajar</span>
                                    <span class="detail-value">
                                        @if($user->kartu_pelajar_path)
                                            <a href="{{ route('users.document', [$user, 'kartu-pelajar']) }}" target="_blank" rel="noopener" class="btn-tool" style="text-decoration:none;"><i class="fa-solid fa-eye"></i> Lihat Kartu Pelajar</a>
                                        @else
                                            Belum diunggah
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section" style="margin-bottom:0;">
                            <h5><i class="fa-solid fa-clock-rotate-left"></i> Informasi Sistem</h5>
                            <div class="detail-grid">
                                <div class="detail-row">
                                    <span class="detail-label">ID Database</span>
                                    <span class="detail-value">{{ $user->id }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Tanggal Dibuat</span>
                                    <span class="detail-value">{{ $dibuat }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Terakhir Diperbarui</span>
                                    <span class="detail-value">{{ $diperbarui }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-footer">
                        <a href="{{ route('users.index') }}" class="btn-modal-cancel" style="text-decoration:none;">
                            <i class="fa-solid fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <!-- ===== FOOTER ===== -->
                <footer class="footer">
                    <span>© 2026 PT Sandya Network</span>
                    <span class="footer-sep">•</span>
                    <span>Sistem Absensi PKL</span>
                </footer>
            </main>
        </div>
    </div>

    <script src="{{ asset('js/dashboard.js') }}"></script>
    @if(auth()->check() && auth()->user()->role === 'admin')
    <script src="{{ asset('js/admin-notifikasi.js') }}"></script>
    @endif
</body>
</html>
