@php
    // Badge mapping untuk status (mendukung status_kehadiran baru).
    $badge = [
        'Masuk'        => 'badge-masuk',
        'Telat'        => 'badge-telat',
        'Tidak Masuk'  => 'badge-tidak',
        'Pulang'       => 'badge-pulang',
        'Belum Pulang' => 'badge-belum',
        'Izin'         => 'badge-izin',
        'Dispensasi'   => 'badge-dispensasi',
        'Sakit'        => 'badge-sakit',
    ];
    $cari = $filters['cari'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Absensi PKL Sandya Network</title>
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
                <div class="brand-logo">
                    <img src="{{ asset('images/logo-sandya2.png') }}" alt="Sandya Absensi PKL">
                </div>
                <div class="brand-text">
                    <span class="brand-name">Sandya</span>
                    <span class="brand-sub">Absensi PKL</span>
                </div>
                <button class="sidebar-close" id="sidebarClose" aria-label="Tutup menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <p class="nav-section">Menu Utama</p>
                <a href="{{ route('dashboard') }}" class="nav-link active" data-tooltip="Dashboard">
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
                @if (($role ?? 'siswa') !== 'admin')
                <a href="{{ route('rekap.siswa') }}" class="nav-link" data-tooltip="Riwayat Rekap">
                    <i class="fa-solid fa-chart-column"></i>
                    <span>Riwayat Rekap</span>
                </a>
                @endif

<p class="nav-section">Lainnya</p>
                @if (($role ?? 'siswa') === 'admin')
                <a href="{{ route('data-absen-masuk.index') }}" class="nav-link" data-tooltip="Data Absen">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Data Absen</span>
                </a>
                <a href="{{ route('rekap.admin') }}" class="nav-link" data-tooltip="Rekap Absen">
                    <i class="fa-solid fa-chart-column"></i>
                    <span>Rekap Absen</span>
                </a>
                <a href="{{ route('users.index') }}" class="nav-link" data-tooltip="Menu User">
                    <i class="fa-solid fa-users"></i>
                    <span>Menu User</span>
                </a>
                <a href="{{ route('lokasi-kantor.index') }}" class="nav-link" data-tooltip="Pengaturan Lokasi Kantor">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Pengaturan Lokasi</span>
                </a>
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
<span class="profile-name">{{ $username ?? 'Admin' }}</span>
                            <span class="profile-role">{{ ($role ?? 'siswa') === 'admin' ? 'Administrator' : 'Siswa PKL' }}</span>
                        </div>
                        <button class="icon-btn" id="profileDropdownBtn" aria-label="Dropdown profil">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>

<div class="dropdown-menu" id="profileDropdown">
                            @if (($role ?? 'siswa') === 'admin')
                            <a href="{{ route('users.index') }}" class="dropdown-item"><i class="fa-solid fa-users"></i> Menu User</a>
                            <div class="dropdown-divider"></div>
                            @endif
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
                <div class="page-title">
                    <h1 class="page-title-main">Dashboard</h1>
                    @if (($role ?? 'siswa') === 'admin')
                    <p class="page-title-sub">
                        Selamat Datang, <strong>{{ $username ?? 'Admin' }}</strong> — Kelola seluruh data absensi siswa PKL dengan mudah.
                    </p>
                    @else
                    <p class="page-title-sub">
                        Selamat Datang, <strong>{{ $username ?? 'Siswa' }}</strong> — Pantau kehadiran dan aktivitas PKL Anda hari ini.
                    </p>
                    @endif
                </div>

@if (($role ?? 'siswa') === 'admin')
                <!-- ===== STAT CARDS — ADMIN (global seluruh siswa) ===== -->
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#0F4C81">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="masuk">{{ $totalMasuk }}</div>
                            <div class="stat-label">Jumlah Siswa Masuk Hari Ini</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#3B82F6">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="laporan">{{ $totalLaporan }}</div>
                            <div class="stat-label">Jumlah Laporan Hari Ini</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#10B981">
                            <i class="fa-solid fa-door-open"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="pulang">{{ $totalPulang }}</div>
                            <div class="stat-label">Jumlah Siswa Pulang Hari Ini</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#8B5CF6">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="siswa">{{ $totalSiswa }}</div>
                            <div class="stat-label">Total Siswa PKL Aktif</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#EF4444">
                            <i class="fa-solid fa-user-slash"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="user-nonaktif">{{ $totalUserNonaktif }}</div>
                            <div class="stat-label">User Nonaktif</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#F59E0B">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="persentase">{{ $persentaseKehadiran }}%</div>
                            <div class="stat-label">Persentase Kehadiran Hari Ini</div>
                        </div>
                    </div>
                </div>

                <!-- ===== REKAP PER JENIS (Masuk/Telat/Tidak Masuk/Izin/Dispensasi/Sakit) — ADMIN ===== -->
                <div class="stat-grid" style="margin-top:-14px; margin-bottom:28px">
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#F59E0B">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="telat">{{ $totalTelat ?? 0 }}</div>
                            <div class="stat-label">Jumlah Telat Hari Ini</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#DC2626">
                            <i class="fa-solid fa-user-xmark"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="tidak-masuk">{{ $totalTidakMasuk ?? 0 }}</div>
                            <div class="stat-label">Jumlah Tidak Masuk Hari Ini</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#F59E0B">
                            <i class="fa-solid fa-envelope-open-text"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="izin">{{ $totalIzin ?? 0 }}</div>
                            <div class="stat-label">Jumlah Izin Hari Ini</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#3B82F6">
                            <i class="fa-solid fa-file-circle-check"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="dispensasi">{{ $totalDispensasi ?? 0 }}</div>
                            <div class="stat-label">Jumlah Dispensasi Hari Ini</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#EF4444">
                            <i class="fa-solid fa-house-medical"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="sakit">{{ $totalSakit ?? 0 }}</div>
                            <div class="stat-label">Jumlah Sakit Hari Ini</div>
                        </div>
                    </div>
                </div>
@else
                <!-- ===== STAT CARDS — SISWA (privat milik user yang login) ===== -->
                @php
                    $sMasuk = $myAbsenMasukHariIni ?? null;
                    $sPulang = $myAbsenPulangHariIni ?? null;
                    $sMasukLabel = $sMasuk ? ($sMasuk->status_kehadiran ?? $sMasuk->status ?? 'Masuk') : null;
                    $sPulangLabel = $sPulang ? ($sPulang->status_kehadiran ?? $sPulang->status ?? 'Masuk') : null;
                    $isExemptMasuk = $sMasukLabel ? in_array($sMasukLabel, ['Izin','Dispensasi','Sakit'], true) : false;
                    $isExemptPulang = $sPulangLabel ? in_array($sPulangLabel, ['Izin','Dispensasi','Sakit'], true) : false;
                    $hasMasukHariIni = $sMasuk && !empty($sMasuk->jam_masuk);
                    $hasPulangHariIni = $sPulang && !empty($sPulang->jam_pulang);
                    $isTelat = $sMasukLabel === 'Telat';
                    $isTidakMasuk = ($myIsTidakMasukHariIni ?? false) && !$hasMasukHariIni && !$isExemptMasuk;
                @endphp
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#0F4C81">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        </div>
                        <div class="stat-info">
                            @if ($hasMasukHariIni)
                                @if($isTelat)
                                    <div class="stat-value ripple" data-stat="my-masuk" style="font-size:1.15rem;color:#B45309;">Telat</div>
                                    <div class="stat-label">Absen Masuk Hari Ini — {{ $sMasuk->jam_masuk }} (Telat)</div>
                                @else
                                    <div class="stat-value ripple" data-stat="my-masuk" style="font-size:1.15rem;color:#15803D;">Sudah Absen</div>
                                    <div class="stat-label">Absen Masuk Hari Ini — {{ $sMasuk->jam_masuk }} ({{ $sMasukLabel }})</div>
                                @endif
                            @elseif ($sMasuk && $isExemptMasuk)
                                <div class="stat-value ripple" data-stat="my-masuk" style="font-size:1.15rem;color:#0F4C81;">{{ $sMasukLabel }}</div>
                                <div class="stat-label">Absen Masuk Hari Ini — {{ \Carbon\Carbon::parse($sMasuk->tgl_masuk)->format('d-m-Y') }}</div>
                            @elseif($isTidakMasuk)
                                <div class="stat-value ripple" data-stat="my-masuk" style="font-size:1.15rem;color:#991B1B;">Tidak Masuk</div>
                                <div class="stat-label">Absen Masuk Hari Ini — Tidak Masuk (setelah 17:00)</div>
                            @else
                                <div class="stat-value ripple" data-stat="my-masuk" style="font-size:1.15rem;color:#6B7280;">Belum Absen</div>
                                <div class="stat-label">Absen Masuk Hari Ini @if(\App\Support\WaktuAbsen::sudahLewatMulaiPulang(now())) <span style="font-size:0.70rem;">(sebelum 17:00 belum dihitung)</span>@endif</div>
                            @endif
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#10B981">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </div>
                        <div class="stat-info">
                            @if ($hasPulangHariIni)
                                <div class="stat-value ripple" data-stat="my-pulang" style="font-size:1.15rem;color:#15803D;">Sudah Absen</div>
                                <div class="stat-label">Absen Pulang Hari Ini — {{ $sPulang->jam_pulang }}</div>
                            @elseif ($isExemptPulang)
                                <div class="stat-value ripple" data-stat="my-pulang" style="font-size:1.05rem;color:#0F4C81;">Tidak Diperlukan</div>
                                <div class="stat-label">Absen Pulang Hari Ini — {{ $sPulangLabel }} (otomatis selesai)</div>
                            @else
                                <div class="stat-value ripple" data-stat="my-pulang" style="font-size:1.15rem;color:#DC2626;">Belum Absen</div>
                                <div class="stat-label">Absen Pulang Hari Ini</div>
                            @endif
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#3B82F6">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="my-total-masuk">{{ $myTotalKehadiran ?? 0 }}</div>
                            <div class="stat-label">Total Kehadiran Saya</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#F59E0B">
                            <i class="fa-solid fa-envelope-open-text"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="my-izin">{{ $myTotalIzin ?? 0 }}</div>
                            <div class="stat-label">Total Izin Saya</div>
                        </div>
                    </div>
                </div>
                <div class="stat-grid" style="margin-top:-14px; margin-bottom:28px">
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#F59E0B">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="my-telat">{{ $myTotalTelat ?? 0 }}</div>
                            <div class="stat-label">Total Telat Saya</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#DC2626">
                            <i class="fa-solid fa-user-xmark"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="my-tidak-masuk">{{ $myTotalTidakMasuk ?? 0 }}</div>
                            <div class="stat-label">Total Tidak Masuk Saya</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#0F4C81">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="my-laporan">{{ $myJumlahLaporan ?? 0 }}</div>
                            <div class="stat-label">Jumlah Laporan</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#8B5CF6">
                            <i class="fa-solid fa-file-circle-check"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="my-dispensasi">{{ $myTotalDispensasi ?? 0 }}</div>
                            <div class="stat-label">Total Dispensasi Saya</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="--scolor:#EF4444">
                            <i class="fa-solid fa-house-medical"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value ripple" data-stat="my-sakit">{{ $myTotalSakit ?? 0 }}</div>
                            <div class="stat-label">Total Sakit Saya</div>
                        </div>
                    </div>
                </div>

@endif

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
