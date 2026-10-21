@php
    $sudahMasuk  = $absensiHariIni !== null && $absensiHariIni->jam_masuk !== null;
    $sudahPulang = $absensiHariIni !== null && $absensiHariIni->jam_pulang !== null;
    // Logika utama: Masuk wajib pulang, Izin/Dispensasi/Sakit tidak perlu pulang
    $statusKehadiran = $statusKehadiran ?? ($absensiHariIni?->status_kehadiran ?? $absensiHariIni?->status ?? null);
    $isExempt = $isExempt ?? ($absensiHariIni !== null && in_array($statusKehadiran, ['Izin','Dispensasi','Sakit'], true));
    $isSelesaiTanpaPulang = $isSelesaiTanpaPulang ?? ($isExempt && $sudahMasuk);
    // Fallback jika controller tidak mengirim variabel
    if (!isset($isSelesaiTanpaPulang)) {
        $isSelesaiTanpaPulang = $isExempt && $sudahMasuk;
    }
    $bolehAbsen  = $sudahMasuk && ! $sudahPulang && ! $isSelesaiTanpaPulang;
    $namaTampil  = $user->nama ?: $user->username;
    $sekarangWib = now();

    // Jendela Absen Pulang FIXED 17:00 - 23:00 WIB.
    // Semua siswa memiliki waktu mulai sama 17:00, tidak tergantung jam masuk.
    // Dihitung server, hanya ditampilkan di sini.
    // Untuk status exempt, jendela tidak relevan.
    $jamMasukPulang   = $waktuAbsen['jamMasuk'] ?? $absensiHariIni?->jam_masuk;
    $mulaiPulang      = $waktuAbsen['mulai'] ?? \App\Support\WaktuAbsen::mulai('pulang');
    $selesaiPulang    = $waktuAbsen['selesai'] ?? \App\Support\WaktuAbsen::selesai('pulang');

    $absenJsConfig = [
        'namaKantor'   => $lokasi['nama'],
        'alamatKantor' => $lokasi['alamat'],
        'lat'          => (float) $lokasi['latitude'],
        'lng'          => (float) $lokasi['longitude'],
        'radius'       => (float) $lokasi['radius'],
        'gpsWajib'     => (bool) config('absensi.gps_wajib', true),
        'maxAccuracy'  => (float) config('absensi.max_accuracy', 100),
        'fotoWajib'    => (bool) config('absensi.foto_wajib', true),
        'maxFotoKb'    => (int) config('absensi.max_foto_kb', 3072),
        'serverTime'   => $sekarangWib->format('Y-m-d H:i:s'),
        'jamMulai'     => $mulaiPulang,
        'jamSelesai'   => $selesaiPulang,
        'waktuStatus'  => $waktuAbsen['status'] ?? 'belum',
        'jamMasuk'     => $jamMasukPulang,
        'isExempt'     => $isExempt,
        'isSelesaiTanpaPulang' => $isSelesaiTanpaPulang,
        'statusKehadiran' => $statusKehadiran,
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absen Pulang - Sistem Absensi PKL Sandya Network</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    <link href="{{ asset('css/absen.css') }}" rel="stylesheet">
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
                <button class="sidebar-close" id="sidebarClose" aria-label="Tutup menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
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
                <a href="{{ route('absen.pulang') }}" class="nav-link active" data-tooltip="Absen Pulang">
                    <i class="fa-solid fa-arrow-right-from-bracket" style="color:#F87171"></i>
                    <span>Absen Pulang</span>
                </a>
                <a href="{{ route('laporan.index') }}" class="nav-link" data-tooltip="Laporan">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Laporan</span>
                </a>
                @if (($role ?? $userSession['role'] ?? 'siswa') !== 'admin')
                <a href="{{ route('rekap.siswa') }}" class="nav-link" data-tooltip="Riwayat Rekap"><i class="fa-solid fa-chart-column"></i><span>Riwayat Rekap</span></a>
                @endif

                <p class="nav-section">Lainnya</p>
                @if (($userSession['role'] ?? 'siswa') === 'admin')
                <a href="{{ route('data-absen-masuk.index') }}" class="nav-link" data-tooltip="Data Absen">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Data Absen</span>
                </a>
                <a href="{{ route('rekap.admin') }}" class="nav-link" data-tooltip="Rekap Absen"><i class="fa-solid fa-chart-column"></i><span>Rekap Absen</span></a>
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
                            <img src="https://i.pravatar.cc/100?img=12" alt="Foto Profil">
                        </div>
                        <div class="profile-meta">
                            <span class="profile-name">{{ $userSession['username'] ?? 'User' }}</span>
                            <span class="profile-role">{{ ($userSession['role'] ?? 'siswa') === 'admin' ? 'Administrator' : 'Siswa PKL' }}</span>
                        </div>
                        <button class="icon-btn" id="profileDropdownBtn" aria-label="Dropdown profil">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>

                        <div class="dropdown-menu" id="profileDropdown">
                            @if (($userSession['role'] ?? 'siswa') === 'admin')
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
                @if (($isAdmin ?? false))
                <!-- ============ ADMIN: TABEL ABSEN PULANG ============ -->
                <div class="page-title">
                    <h1 class="page-title-main">Absen Pulang — Data Siswa</h1>
                    <p class="page-title-sub">
                        Daftar siswa yang melakukan Absen Pulang pada <strong>{{ \Carbon\Carbon::parse($tanggalHariIni)->translatedFormat('l, d F Y') }}</strong>. Data diambil dinamis dari database.
                    </p>
                </div>

                @if (session('absen_success'))
                <div class="absen-alert success"><i class="fa-solid fa-circle-check"></i><span>{{ session('absen_success') }}</span></div>
                @endif
                @if (session('absen_error'))
                <div class="absen-alert error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ session('absen_error') }}</span></div>
                @endif

                <div class="card-table">
                    <div class="card-table-header">
                        <div class="ct-title">
                            <i class="fa-solid fa-clipboard-check"></i>
                            <div>
                                <h3>Absen Pulang Hari Ini — {{ \Carbon\Carbon::parse($tanggalHariIni)->format('d-m-Y') }}</h3>
                                <p>Menampilkan <strong>{{ $items->count() }}</strong> data. Foto pulang di kolom paling kiri sebagai thumbnail.</p>
                            </div>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="activity-table admin-absen-table">
                            <thead>
                                <tr>
                                    <th style="width:70px">Foto</th>
                                    <th style="width:55px">Nomor</th>
                                    <th>Nama</th>
                                    <th>Status</th>
                                    <th>Jam Absen</th>
                                    <th>Tanggal</th>
                                    <th style="width:130px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $row)
                                <tr>
                                    <td>
                                        @if ($row->foto_pulang)
                                        <img src="{{ route('foto.lihat', $row->id) }}?tipe=pulang" alt="Foto {{ $row->nama_tampil }}" class="admin-foto-thumb" loading="lazy">
                                        @else
                                        <span class="foto-absensi-empty" style="font-size:0.68rem">Tidak ada foto</span>
                                        @endif
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><div class="cell-nama" style="font-weight:600; color:var(--text-main)">{{ $row->nama_tampil }}</div></td>
                                    <td><span class="badge-status badge-pulang">Pulang</span></td>
                                    <td style="font-variant-numeric:tabular-nums; font-weight:600">{{ $row->jam_pulang ? \Carbon\Carbon::parse($row->jam_pulang)->format('H:i') : '-' }}</td>
                                    <td>{{ $row->tgl_pulang ? \Carbon\Carbon::parse($row->tgl_pulang)->format('d-m-Y') : ($row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->format('d-m-Y') : '-') }}</td>
                                    <td><button type="button" class="btn-tool btn-detail" data-detail-id="{{ $row->id }}"><i class="fa-solid fa-eye"></i> Lihat Detail</button></td>
                                </tr>
                                @empty
                                <tr><td colspan="7"><div class="table-empty"><i class="fa-solid fa-inbox"></i><p>Belum ada siswa yang melakukan Absen Pulang hari ini.</p></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Detail Pulang -->
                <div class="modal-overlay" id="detailOverlay" aria-hidden="true">
                    <div class="modal modal-lg">
                        <div class="modal-header">
                            <h3>Detail Absen Pulang</h3>
                            <button type="button" class="modal-close" id="detailClose" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="detail-hero">
                            <div class="detail-hero-photo">
                                <img id="detailFotoImg" src="" alt="Foto Pulang" hidden style="width:140px; height:140px; object-fit:cover; border-radius:12px; border:2px solid #E5E7EB;">
                                <span id="detailFotoEmpty" class="detail-foto-empty" hidden>Foto tidak tersedia</span>
                            </div>
                            <div class="detail-hero-info">
                                <span class="detail-role-label">Nama Siswa</span>
                                <h4 id="detailNama">-</h4>
                                <div class="detail-meta" style="margin-top:6px">
                                    <span><i class="fa-solid fa-calendar"></i> <span id="detailTanggal">-</span> (<span id="detailHari">-</span>)</span>
                                    <span><i class="fa-solid fa-clock"></i> Jam Pulang: <span id="detailJam">-</span> WIB</span>
                                    <span><i class="fa-solid fa-right-to-bracket"></i> Jam Masuk: <span id="detailJamMasuk">-</span> WIB</span>
                                    <span><span class="badge-status badge-pulang" id="detailStatusBadge">Pulang</span></span>
                                </div>
                                <p id="detailKeterangan" style="font-size:0.82rem;color:var(--text-sec);margin-top:8px"></p>
                            </div>
                        </div>
                        <div class="detail-section">
                            <h5><i class="fa-solid fa-camera"></i> Foto Absen Pulang</h5>
                            <div style="display:flex; justify-content:center;">
                                <div style="text-align:center;">
                                    <img id="detailFotoPulangImg" class="foto-detail-dua" src="" alt="Foto Absen Pulang" hidden style="width:100%; max-width:320px; border-radius:10px; border:1px solid #E5E7EB;">
                                    <span id="detailFotoPulangEmpty" class="foto-absensi-empty" hidden>Foto tidak tersedia</span>
                                </div>
                            </div>
                        </div>
                        <div class="detail-section">
                            <h5><i class="fa-solid fa-location-crosshairs"></i> Informasi Lokasi / GPS Pulang</h5>
                            <div class="detail-grid">
                                <div class="detail-row"><span class="detail-label">Latitude</span><span class="detail-value" id="detailLat">-</span></div>
                                <div class="detail-row"><span class="detail-label">Longitude</span><span class="detail-value" id="detailLng">-</span></div>
                                <div class="detail-row"><span class="detail-label">Akurasi</span><span class="detail-value" id="detailAkurasi">-</span></div>
                                <div class="detail-row"><span class="detail-label">Jarak</span><span class="detail-value" id="detailJarak">-</span></div>
                                <div class="detail-row"><span class="detail-label">Radius Kantor</span><span class="detail-value" id="detailRadius">-</span></div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn-modal-cancel" id="detailTutup">Tutup</button></div>
                    </div>
                </div>

                <script>
                    window.ADMIN_DETAILS = @json($details);
                    (function(){
                        var details = window.ADMIN_DETAILS || {};
                        var overlay = document.getElementById('detailOverlay');
                        function close(){ overlay.classList.remove('show'); overlay.setAttribute('aria-hidden','true'); }
                        document.querySelectorAll('.btn-detail').forEach(function(btn){
                            btn.addEventListener('click', function(){
                                var d = details[btn.dataset.detailId]; if(!d) return;
                                document.getElementById('detailNama').textContent = d.nama || '-';
                                document.getElementById('detailTanggal').textContent = d.tanggal || '-';
                                var h=document.getElementById('detailHari'); if(h) h.textContent=d.hari||'-';
                                document.getElementById('detailJam').textContent = d.jam_pulang || '-';
                                var jm=document.getElementById('detailJamMasuk'); if(jm) jm.textContent=d.jam_masuk||'-';
                                var img=document.getElementById('detailFotoImg'); var empty=document.getElementById('detailFotoEmpty');
                                if(d.foto){ img.src=d.foto; img.hidden=false; empty.hidden=true; } else { img.hidden=true; empty.hidden=false; }
                                var imgP=document.getElementById('detailFotoPulangImg'); var emptyP=document.getElementById('detailFotoPulangEmpty');
                                if(d.foto){ imgP.src=d.foto; imgP.hidden=false; emptyP.hidden=true; } else { imgP.hidden=true; emptyP.hidden=false; }
                                document.getElementById('detailLat').textContent = d.latitude!=null ? Number(d.latitude).toFixed(6) : '-';
                                document.getElementById('detailLng').textContent = d.longitude!=null ? Number(d.longitude).toFixed(6) : '-';
                                document.getElementById('detailAkurasi').textContent = d.accuracy!=null ? '±'+Number(d.accuracy).toFixed(1)+' m' : '-';
                                document.getElementById('detailJarak').textContent = d.jarak!=null ? Number(d.jarak).toFixed(0)+' m' : '-';
                                document.getElementById('detailRadius').textContent = d.radius!=null ? Number(d.radius).toFixed(0)+' m' : '-';
                                overlay.classList.add('show'); overlay.setAttribute('aria-hidden','false');
                            });
                        });
                        var c1=document.getElementById('detailClose'); if(c1) c1.addEventListener('click', close);
                        var c2=document.getElementById('detailTutup'); if(c2) c2.addEventListener('click', close);
                        overlay.addEventListener('click', function(e){ if(e.target===overlay) close(); });
                        document.addEventListener('keydown', function(e){ if(e.key==='Escape') close(); });
                    })();
                </script>

                @else
                <div class="page-title">
                    <h1 class="page-title-main">Absen Pulang</h1>
                    <p class="page-title-sub">
                        Selamat datang, <strong>{{ $namaTampil }}</strong> — ambil foto selfie dan aktifkan GPS untuk absen pulang.
                    </p>
                </div>

                @if (session('absen_success'))
                <div class="absen-alert success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('absen_success') }}</span>
                </div>
                @endif

                @if (session('absen_error'))
                <div class="absen-alert error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ session('absen_error') }}</span>
                </div>
                @endif

                @if ($errors->any())
                <div class="absen-alert error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <!-- ===== STATUS JAM ABSEN PULANG (WIB) ===== -->
                @if ($isSelesaiTanpaPulang)
                <div class="waktu-banner buka" style="background:#ECFDF5;border-color:#A7F3D0">
                    <div class="waktu-banner-icon" style="background:#10B981">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="waktu-banner-info">
                        <h3 id="waktuBannerTitle" style="color:#065F46">Absensi selesai — Anda tidak perlu melakukan Absen Pulang karena status Anda adalah {{ $statusKehadiran }}.</h3>
                        <p id="waktuBannerSub" style="color:#047857">Status {{ $statusKehadiran }} dianggap selesai otomatis tanpa Absen Pulang.</p>
                        <span class="waktu-countdown" id="waktuCountdown">Tidak perlu Absen Pulang untuk status {{ $statusKehadiran }}</span>
                    </div>
                    <div class="waktu-banner-range">
                        <span class="wb-label">Status Kehadiran</span>
                        <strong>{{ $statusKehadiran }}</strong>
                    </div>
                </div>
                @else
                <div class="waktu-banner {{ $waktuAbsen['status'] }}">
                    <div class="waktu-banner-icon">
                        <i class="fa-solid {{ $waktuAbsen['status'] === 'buka' ? 'fa-door-open' : 'fa-clock' }}"></i>
                    </div>
                    <div class="waktu-banner-info">
                        <h3 id="waktuBannerTitle">
                            @if (! $sudahMasuk)
                            Absen Pulang belum dapat dilakukan.
                            @elseif ($waktuAbsen['status'] === 'belum')
                            Absen Pulang belum dibuka.
                            @elseif ($waktuAbsen['status'] === 'buka')
                            Absen Pulang sudah dibuka.
                            @else
                            Absen Pulang sudah ditutup.
                            @endif
                        </h3>
                        <p id="waktuBannerSub">
                            @if (! $sudahMasuk)
                            Anda belum melakukan Absen Masuk hari ini. Lakukan Absen Masuk terlebih dahulu.
                            @elseif ($waktuAbsen['status'] === 'belum')
                            Anda dapat melakukan Absen Pulang mulai pukul {{ $mulaiPulang }} WIB.
                            @elseif ($waktuAbsen['status'] === 'buka')
                            Silakan lengkapi foto dan GPS, lalu tekan tombol Absen Pulang.
                            @else
                            Batas waktu Absen Pulang adalah pukul {{ $selesaiPulang }} WIB.
                            @endif
                        </p>
                        <span class="waktu-countdown" id="waktuCountdown"></span>
                    </div>
                    <div class="waktu-banner-range">
                        <span class="wb-label">Jam Absen Pulang</span>
                        <strong>{{ $sudahMasuk ? $mulaiPulang.' - '.$selesaiPulang.' WIB' : 'Belum tersedia' }}</strong>
                    </div>
                </div>
                @endif

                <!-- ===== INFORMASI WAKTU ABSEN PULANG SISWA (hidden per request: UI ringkas mobile-friendly) ===== -->
                @if ($sudahMasuk && ! $isSelesaiTanpaPulang)
                <div class="waktu-banner info-mini pulang-jadwal-info-box" style="display:none !important;" aria-hidden="true" hidden>
                    <div class="waktu-banner-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div class="waktu-banner-info">
                        <div class="waktu-banner-mini-row">
                            <span>Jam Masuk:</span>
                            <strong id="infoJamMasuk">{{ \Carbon\Carbon::parse($jamMasukPulang)->format('H:i') }} WIB</strong>
                        </div>
                        <div class="waktu-banner-mini-row">
                            <span>Absen Pulang tersedia:</span>
                            <strong id="infoMulaiPulang">{{ $mulaiPulang }} WIB</strong>
                        </div>
                        <div class="waktu-banner-mini-row">
                            <span>Batas Maksimal:</span>
                            <strong>{{ $selesaiPulang }} WIB</strong>
                        </div>
                        <div class="waktu-banner-mini-row">
                            <span>Aturan:</span>
                            <strong>Siswa dapat melakukan absen pulang mulai pukul {{ $mulaiPulang }} WIB.</strong>
                        </div>
                    </div>
                </div>
                @elseif ($isSelesaiTanpaPulang)
                <div class="waktu-banner info-mini" style="background:#ECFDF5;border-color:#A7F3D0">
                    <div class="waktu-banner-icon" style="background:#10B981">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="waktu-banner-info">
                        <div class="waktu-banner-mini-row">
                            <span>Status Hari Ini:</span>
                            <strong>{{ $statusKehadiran }}</strong>
                        </div>
                        <div class="waktu-banner-mini-row">
                            <span>Jam Tercatat:</span>
                            <strong>{{ \Carbon\Carbon::parse($jamMasukPulang)->format('H:i') }} WIB</strong>
                        </div>
                        <div class="waktu-banner-mini-row">
                            <span>Absen Pulang:</span>
                            <strong style="color:#10B981">Tidak Diperlukan</strong>
                        </div>
                    </div>
                </div>
                @endif

                <!-- ===== BELUM ABSEN MASUK ===== -->
                @if (! $sudahMasuk)
                <div class="absen-done" style="background:#FEF3C7;border-color:#FDE68A">
                    <div class="absen-done-icon" style="background:#F59E0B">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div>
                        <h3 style="color:#92400E">Anda belum melakukan Absen Masuk hari ini</h3>
                        <p style="color:#92400E">Absen Pulang hanya dapat dilakukan setelah Absen Masuk tercatat.</p>
                    </div>
                    <div class="absen-done-action">
                        <a href="{{ route('absen.masuk') }}" class="btn-absen btn-absen-inline">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Absen Masuk
                        </a>
                    </div>
                </div>
                @endif

                <!-- ===== SELESAI TANPA PULANG (Izin/Dispensasi/Sakit) ===== -->
                @if ($isSelesaiTanpaPulang)
                <div class="absen-done" style="background:#ECFDF5;border-color:#A7F3D0">
                    <div class="absen-done-icon" style="background:#10B981">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <h3 style="color:#065F46">Absensi selesai — Anda tidak perlu melakukan Absen Pulang karena status Anda adalah {{ $statusKehadiran }}.</h3>
                        <p style="color:#065F46">Status {{ $statusKehadiran }} tercatat pada pukul {{ \Carbon\Carbon::parse($absensiHariIni->jam_masuk)->format('H:i') }} WIB dan dianggap selesai otomatis tanpa Absen Pulang.</p>
                        <span class="radius-pill ok" style="margin-top:10px;background:#D1FAE5;color:#065F46">
                            <i class="fa-solid fa-circle-check"></i> Tidak perlu Absen Pulang — {{ $statusKehadiran }}
                        </span>
                    </div>
                    <div class="done-time">
                        <span class="dt-label">Status</span>
                        <span class="dt-value" style="font-size:1.0rem">{{ $statusKehadiran }}</span>
                        <span class="dt-label">Jam tercatat {{ \Carbon\Carbon::parse($absensiHariIni->jam_masuk)->format('H:i') }} WIB</span>
                    </div>
                </div>
                @endif

                <!-- ===== SUDAH PULANG ===== -->
                @if ($sudahPulang)
                <div class="absen-done">
                    <div class="absen-done-icon">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <h3>Anda sudah melakukan absen pulang hari ini</h3>
                        <p>Absensi tidak perlu diulang. Terima kasih, sampai jumpa!</p>
                        @if ($absensiHariIni->foto_pulang)
                        <img src="{{ route('foto.lihat', $absensiHariIni->id) }}?tipe=pulang" alt="Foto Absen Pulang" class="foto-absen-sudah">
                        @endif
                    </div>
                    <div class="done-time">
                        <span class="dt-label">Jam pulang tercatat</span>
                        <span class="dt-value">{{ \Carbon\Carbon::parse($absensiHariIni->jam_pulang)->format('H:i') }}</span>
                        <span class="dt-label">Waktu Server (WIB)</span>
                    </div>
                </div>
                @endif

                <!-- ===== GRID KARTU ===== -->
                <div class="absen-grid">

                    <!-- Kartu Waktu -->
                    <div class="absen-card">
                        <div class="absen-card-header">
                            <i class="fa-solid fa-clock"></i>
                            <h3>Waktu Server (WIB)</h3>
                        </div>
                        <div class="absen-clock">
                            <span id="clockTime">--:--</span><span class="clock-sec" id="clockSec"></span>
                        </div>
                        <div class="absen-date">
                            <i class="fa-regular fa-calendar"></i> {{ $sekarangWib->translatedFormat('l, d F Y') }}
                        </div>
                        <div style="text-align:center">
                            <span class="absen-server-chip">
                                <i class="fa-solid fa-server"></i> Waktu resmi dari server (Asia/Jakarta)
                            </span>
                        </div>
                    </div>

                    <!-- Kartu Status Absensi Hari Ini -->
                    <div class="absen-card">
                        <div class="absen-card-header">
                            <i class="fa-solid fa-list-check"></i>
                            <h3>Status Absensi Hari Ini</h3>
                        </div>
                        <div class="loc-list">
                            <div class="loc-row">
                                <span>
                                    <i class="fa-solid fa-arrow-right-to-bracket" style="color:#10B981;margin-right:6px"></i>
                                    Jam Masuk
                                </span>
                                <strong>{{ $sudahMasuk ? \Carbon\Carbon::parse($absensiHariIni->jam_masuk)->format('H:i') . ' WIB' : 'Belum tercatat' }}</strong>
                            </div>
                            @if ($isSelesaiTanpaPulang)
                            <div class="loc-row">
                                <span>
                                    <i class="fa-solid fa-circle-info" style="color:#10B981;margin-right:6px"></i>
                                    Status
                                </span>
                                <strong style="color:#10B981">{{ $statusKehadiran }}</strong>
                            </div>
                            <div class="loc-row">
                                <span>
                                    <i class="fa-solid fa-clock" style="color:#10B981;margin-right:6px"></i>
                                    Absen Pulang tersedia
                                </span>
                                <strong style="color:#10B981">Tidak Diperlukan</strong>
                            </div>
                            <div class="loc-row">
                                <span>
                                    <i class="fa-solid fa-arrow-right-from-bracket" style="color:#10B981;margin-right:6px"></i>
                                    Absen Pulang
                                </span>
                                <strong style="color:#10B981">Tidak Diperlukan — {{ $statusKehadiran }}</strong>
                            </div>
                            @else
                            <div class="loc-row">
                                <span>
                                    <i class="fa-solid fa-clock" style="color:#F59E0B;margin-right:6px"></i>
                                    Jam Pulang
                                </span>
                                <strong>{{ $mulaiPulang }} - {{ $selesaiPulang }} WIB</strong>
                            </div>
                            <div class="loc-row">
                                <span>
                                    <i class="fa-solid fa-clock" style="color:#F59E0B;margin-right:6px"></i>
                                    Absen Pulang tersedia
                                </span>
                                <strong>{{ $sudahMasuk ? $mulaiPulang.' WIB' : '-' }}</strong>
                            </div>
                            <div class="loc-row">
                                <span>
                                    <i class="fa-solid fa-arrow-right-from-bracket" style="color:#F87171;margin-right:6px"></i>
                                    Absen Pulang
                                </span>
                                <strong>{{ $sudahPulang ? \Carbon\Carbon::parse($absensiHariIni->jam_pulang)->format('H:i') : 'Belum tercatat' }}</strong>
                            </div>
                            @endif
                        </div>

                        @if ($sudahPulang)
                        <span class="radius-pill ok" style="margin-top:14px">
                            <i class="fa-solid fa-circle-check"></i> Selesai: sudah pulang
                        </span>
                        @elseif ($isSelesaiTanpaPulang)
                        <span class="radius-pill ok" style="margin-top:14px;background:#D1FAE5;color:#065F46">
                            <i class="fa-solid fa-circle-check"></i> Selesai: {{ $statusKehadiran }} — tidak perlu Absen Pulang
                        </span>
                        @elseif ($sudahMasuk)
                        <span class="radius-pill wait" style="margin-top:14px">
                            <i class="fa-solid fa-hourglass-half"></i> Menunggu absen pulang
                        </span>
                        @else
                        <span class="radius-pill no" style="margin-top:14px">
                            <i class="fa-solid fa-circle-xmark"></i> Belum ada absen masuk hari ini
                        </span>
                        @endif
                    </div>

                    @if ($bolehAbsen)
                    <!-- Kartu Kamera / Selfie -->
                    <div class="absen-card">
                        <div class="absen-card-header">
                            <i class="fa-solid fa-camera"></i>
                            <h3>Foto Selfie Pulang</h3>
                        </div>

                        <div class="cam-status state-loading" id="cameraStatus">
                            <span class="dot"></span><span>Menyiapkan kamera...</span>
                        </div>

                        <div class="cam-stage">
                            <video id="cameraVideo" autoplay playsinline muted></video>
                            <div class="cam-empty" id="cameraEmpty">
                                <i class="fa-solid fa-video"></i>
                                <p>Kamera tidak tersedia / izin ditolak.</p>
                            </div>
                            <img id="fotoPreview" class="cam-foto-preview" alt="Hasil Foto" hidden>
                        </div>

                        <div class="cam-actions">
                            <button type="button" class="cam-btn primary" id="btnAmbilFoto">
                                <i class="fa-solid fa-camera"></i> Ambil Foto
                            </button>
                            <button type="button" class="cam-btn" id="btnAmbilUlang" hidden>
                                <i class="fa-solid fa-rotate-left"></i> Ambil Ulang
                            </button>
                        </div>
                        <div id="fallbackFotoWrap" style="display:none; margin-top:12px; text-align:center;">
                            <p style="font-size:0.82rem; color:var(--text-sec); margin-bottom:8px;">Kamera bermasalah? Pilih foto dari perangkat:</p>
                            <input type="file" id="inputPilihFile" accept="image/*" hidden>
                            <button type="button" class="cam-btn" id="labelPilihFile" hidden><i class="fa-solid fa-image"></i> Pilih Foto dari Perangkat</button>
                        </div>
                        <p class="cam-note" id="cameraNote">
                            Foto menjadi bukti Absen Pulang. @if (config('absensi.foto_wajib', true)) Foto wajib diambil. @endif
                        </p>
                    </div>



                    @endif
                </div>
                <div id="compassNeedle" style="display:none"></div>

                <!-- ===== TOMBOL ABSEN PULANG ===== -->
                <div class="absen-action">
                    @if ($isSelesaiTanpaPulang)
                    <button type="button" class="btn-absen" disabled style="background:#D1FAE5;color:#065F46;box-shadow:none">
                        <i class="fa-solid fa-circle-check"></i> Absensi selesai — Tidak Perlu Absen Pulang
                    </button>
                    <p class="absen-hint" id="absenHint" style="color:#065F46">
                        Absensi selesai — Anda tidak perlu melakukan Absen Pulang karena status Anda adalah {{ $statusKehadiran }}.
                    </p>
                    @else
                    <form method="POST" action="{{ route('absen.pulang.store') }}" id="formAbsenPulang" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="latitude" id="latField" value="{{ old('latitude') }}">
                        <input type="hidden" name="longitude" id="lngField" value="{{ old('longitude') }}">
                        <input type="hidden" name="accuracy" id="accuracyField" value="{{ old('accuracy') }}">
                        <input type="file" name="foto" id="fotoField" accept="image/*" style="display:none">

                        <button type="submit" class="btn-absen" id="btnAbsenPulang"
                                data-boleh-absen="{{ $bolehAbsen ? '1' : '0' }}"
                                data-waktu-status="{{ $waktuAbsen['status'] }}"
                                data-is-exempt="{{ $isSelesaiTanpaPulang ? '1' : '0' }}"
                                data-status-kehadiran="{{ $statusKehadiran ?? '' }}"
                                {{ $bolehAbsen ? '' : 'disabled' }}
                                {{ $waktuAbsen['status'] !== 'buka' && !$isSelesaiTanpaPulang ? 'disabled' : '' }}
                                {{ (config('absensi.gps_wajib', true) && ! $bolehAbsen) ? 'disabled' : '' }}
                                {{ (config('absensi.foto_wajib', true) && ! $bolehAbsen) ? 'disabled' : '' }}>
                            @if ($sudahPulang)
                            <i class="fa-solid fa-circle-check"></i> Absen Pulang Selesai
                            @elseif (! $sudahMasuk)
                            <i class="fa-solid fa-ban"></i> Absen Pulang Tidak Tersedia
                            @else
                            <span class="btn-spinner"><i class="fa-solid fa-spinner fa-spin"></i></span>
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Absen Pulang
                            @endif
                        </button>
                    </form>
                    <p class="absen-hint" id="absenHint">
                        @if ($sudahPulang)
                        Anda sudah tercatat pulang hari ini. Tombol absen dinonaktifkan.
                        @elseif (! $sudahMasuk)
                        Lakukan <a href="{{ route('absen.masuk') }}" style="color:var(--primary)">Absen Masuk</a> terlebih dahulu.
                        @elseif ($waktuAbsen['status'] === 'belum')
                        Anda dapat melakukan Absen Pulang mulai pukul {{ $mulaiPulang }} WIB.
                        @elseif ($waktuAbsen['status'] === 'tutup')
                        Tombol absen dinonaktifkan karena sudah melewati pukul {{ $selesaiPulang }} WIB.
                        @else
                        Tombol aktif setelah foto diambil dan lokasi ditemukan dalam Radius Kantor.
                        @endif
                    </p>
                    @endif
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

    @if (!($isAdmin ?? false))
    <script>
        window.ABSEN_CONFIG = @json($absenJsConfig);
    </script>
    @endif
    <script src="{{ asset('js/dashboard.js') }}?v={{ @filemtime(public_path('js/dashboard.js')) }}"></script>
    @if(auth()->check() && auth()->user()->role === 'admin')
    <script src="{{ asset('js/admin-notifikasi.js') }}"></script>
    @endif
    <script src="{{ asset('js/foto-helper.js') }}?v={{ @filemtime(public_path('js/foto-helper.js')) }}"></script>
    @if (!($isAdmin ?? false))
    <script src="{{ asset('js/absen-pulang.js') }}?v={{ @filemtime(public_path('js/absen-pulang.js')) }}"></script>
    @endif
</body>
</html>
