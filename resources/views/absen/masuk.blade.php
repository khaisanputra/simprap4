@php
    $sudahAbsen  = $absensiHariIni !== null && $absensiHariIni->jam_masuk !== null;
    $namaTampil  = $user->nama ?: $user->username;
    $sekarangWib = now();
    $oldStatus   = old('status_kehadiran', 'Masuk');

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
        'sudahAbsen'   => $sudahAbsen,
        'jamMulai'     => $waktuAbsen['mulai'],
        'jamSelesai'   => $waktuAbsen['selesai'],
        'waktuStatus'  => $waktuAbsen['status'],
        'oldStatus'    => $oldStatus,
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absen Masuk - Sistem Absensi PKL Sandya Network</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    <link href="{{ asset('css/absen.css') }}" rel="stylesheet">
    <style>/* Sembunyikan kartu Lokasi GPS secara visual saja, logic GPS tetap berjalan di DOM */ #lokasiCard{display:none !important;}</style>
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
                <a href="{{ route('dashboard') }}" class="nav-link" data-tooltip="Dashboard">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('absen.masuk') }}" class="nav-link active" data-tooltip="Absen Masuk">
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
                <!-- ============ ADMIN: TABEL ABSEN MASUK ============ -->
                <div class="page-title">
                    <h1 class="page-title-main">Absen Masuk — Data Siswa</h1>
                    <p class="page-title-sub">
                        Daftar siswa yang melakukan Absen Masuk pada <strong>{{ \Carbon\Carbon::parse($tanggalHariIni)->translatedFormat('l, d F Y') }}</strong>. Data diambil dinamis dari database.
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
                                <h3>Absen Masuk Hari Ini — {{ \Carbon\Carbon::parse($tanggalHariIni)->format('d-m-Y') }}</h3>
                                <p>Menampilkan <strong>{{ $items->count() }}</strong> data. Foto di kolom paling kiri sebagai thumbnail rapi.</p>
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
                                @php
                                    $kehadiranBadges = [
                                        'Masuk' => 'badge-masuk',
                                        'Telat' => 'badge-telat',
                                        'Tidak Masuk' => 'badge-tidak',
                                        'Izin' => 'badge-izin',
                                        'Dispensasi' => 'badge-dispensasi',
                                        'Sakit' => 'badge-sakit',
                                    ];
                                @endphp
                                @forelse ($items as $idx => $row)
                                <tr>
                                    <td>
                                        @if ($row->foto)
                                        <img src="{{ route('foto.lihat', $row->id) }}" alt="Foto {{ $row->nama_tampil }}" class="admin-foto-thumb" loading="lazy">
                                        @else
                                        <span class="foto-absensi-empty" style="font-size:0.68rem">Tidak ada foto</span>
                                        @endif
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><div class="cell-nama" style="font-weight:600; color:var(--text-main)">{{ $row->nama_tampil }}</div></td>
                                    <td><span class="badge-status {{ $kehadiranBadges[$row->status_label] ?? 'badge-masuk' }}">{{ $row->status_label }}</span></td>
                                    <td style="font-variant-numeric:tabular-nums; font-weight:600">{{ $row->jam_masuk ? \Carbon\Carbon::parse($row->jam_masuk)->format('H:i') : '-' }}</td>
                                    <td>{{ $row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->format('d-m-Y') : '-' }}</td>
                                    <td><button type="button" class="btn-tool btn-detail" data-detail-id="{{ $row->id }}"><i class="fa-solid fa-eye"></i> Lihat Detail</button></td>
                                </tr>
                                @empty
                                <tr><td colspan="7"><div class="table-empty"><i class="fa-solid fa-inbox"></i><p>Belum ada siswa yang melakukan Absen Masuk hari ini.</p></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Detail Masuk -->
                <div class="modal-overlay" id="detailOverlay" aria-hidden="true">
                    <div class="modal modal-lg">
                        <div class="modal-header">
                            <h3>Detail Absen Masuk</h3>
                            <button type="button" class="modal-close" id="detailClose" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="detail-hero">
                            <div class="detail-hero-photo">
                                <img id="detailFotoImg" src="" alt="Foto" hidden style="width:140px; height:140px; object-fit:cover; border-radius:12px; border:2px solid #E5E7EB;">
                                <span id="detailFotoEmpty" class="detail-foto-empty" hidden>Foto tidak tersedia</span>
                            </div>
                            <div class="detail-hero-info">
                                <span class="detail-role-label">Nama Siswa</span>
                                <h4 id="detailNama">-</h4>
                                <div class="detail-meta" style="margin-top:6px">
                                    <span><i class="fa-solid fa-calendar"></i> <span id="detailTanggal">-</span> (<span id="detailHari">-</span>)</span>
                                    <span><i class="fa-solid fa-clock"></i> <span id="detailJam">-</span> WIB</span>
                                    <span><span class="badge-status" id="detailStatusBadge">-</span></span>
                                </div>
                                <p id="detailKeterangan" style="font-size:0.82rem;color:var(--text-sec);margin-top:8px"></p>
                            </div>
                        </div>
                        <div class="detail-section">
                            <h5><i class="fa-solid fa-location-crosshairs"></i> Informasi Lokasi / GPS</h5>
                            <div class="detail-grid">
                                <div class="detail-row"><span class="detail-label">Latitude</span><span class="detail-value" id="detailLat">-</span></div>
                                <div class="detail-row"><span class="detail-label">Longitude</span><span class="detail-value" id="detailLng">-</span></div>
                                <div class="detail-row"><span class="detail-label">Akurasi</span><span class="detail-value" id="detailAkurasi">-</span></div>
                                <div class="detail-row"><span class="detail-label">Jarak</span><span class="detail-value" id="detailJarak">-</span></div>
                                <div class="detail-row"><span class="detail-label">Radius Kantor</span><span class="detail-value" id="detailRadius">-</span></div>
                            </div>
                        </div>
                        <div class="detail-section" id="detailBuktiSection" style="display:none">
                            <h5><i class="fa-solid fa-paperclip"></i> Bukti Pendukung</h5>
                            <a id="detailBuktiLink" href="#" target="_blank" class="btn-tool"><i class="fa-solid fa-file"></i> Lihat Bukti</a> <span id="detailBuktiNama" style="font-size:0.80rem;color:var(--text-sec);margin-left:8px"></span>
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
                                document.getElementById('detailTanggal').title = d.tanggal_long || '';
                                var hariEl = document.getElementById('detailHari'); if(hariEl) hariEl.textContent = d.hari || '-';
                                document.getElementById('detailJam').textContent = d.jam_masuk || '-';
                                var badge = document.getElementById('detailStatusBadge'); if(badge){ badge.textContent = d.status_kehadiran || '-'; badge.className='badge-status ' + ({Masuk:'badge-masuk',Telat:'badge-telat','Tidak Masuk':'badge-tidak',Izin:'badge-izin',Dispensasi:'badge-dispensasi',Sakit:'badge-sakit'}[d.status_kehadiran]||'badge-masuk'); }
                                var ket = document.getElementById('detailKeterangan'); if(ket) ket.textContent = d.keterangan ? '"'+d.keterangan+'"' : '';
                                var img = document.getElementById('detailFotoImg'); var empty=document.getElementById('detailFotoEmpty');
                                if(d.foto){
                                    img.src=d.foto; img.hidden=false; img.style.display='';
                                    empty.hidden=true; empty.style.display='none';
                                    // fallback jika foto 404 -> tampilkan empty
                                    img.onerror=function(){ img.hidden=true; img.style.display='none'; empty.hidden=false; empty.style.display=''; };
                                    img.onload=function(){ empty.hidden=true; empty.style.display='none'; };
                                } else { img.hidden=true; img.style.display='none'; img.src=''; empty.hidden=false; empty.style.display=''; }
                                document.getElementById('detailLat').textContent = d.latitude!=null ? Number(d.latitude).toFixed(6) : '-';
                                document.getElementById('detailLng').textContent = d.longitude!=null ? Number(d.longitude).toFixed(6) : '-';
                                document.getElementById('detailAkurasi').textContent = d.accuracy!=null ? '±'+Number(d.accuracy).toFixed(1)+' m' : '-';
                                document.getElementById('detailJarak').textContent = d.jarak!=null ? Number(d.jarak).toFixed(0)+' m' : '-';
                                document.getElementById('detailRadius').textContent = d.radius!=null ? Number(d.radius).toFixed(0)+' m' : '-';
                                var buktiSec=document.getElementById('detailBuktiSection'); var buktiLink=document.getElementById('detailBuktiLink'); var buktiNama=document.getElementById('detailBuktiNama');
                                if(d.bukti_file){ buktiSec.style.display='block'; buktiLink.href=d.bukti_file; buktiNama.textContent=d.bukti_original_name||''; } else { buktiSec.style.display='none'; }
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
                    <h1 class="page-title-main">Absen Masuk</h1>
                    <p class="page-title-sub">
                        Selamat datang, <strong>{{ $namaTampil }}</strong> — pilih jenis kehadiran, ambil foto selfie dan aktifkan GPS jika memilih Masuk.
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

                <!-- ===== STATUS JAM ABSEN MASUK (WIB) — untuk Masuk saja; Izin/Dispensasi/Sakit bebas waktu ===== -->
                @if ($oldStatus !== 'Masuk')
                <div class="waktu-banner buka">
                    <div class="waktu-banner-icon">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <div class="waktu-banner-info">
                        <h3 id="waktuBannerTitle">Pengajuan {{ $oldStatus }} dapat dilakukan kapan saja</h3>
                        <p id="waktuBannerSub">Tidak ada batas waktu untuk status {{ $oldStatus }}. Silakan ajukan kapan saja.</p>
                        <span class="waktu-countdown" id="waktuCountdown">Bebas waktu — kapan saja</span>
                    </div>
                    <div class="waktu-banner-range">
                        <span class="wb-label">Jam Absen</span>
                        <strong>{{ $waktuAbsen['mulai'] }} - {{ $waktuAbsen['selesai'] }} WIB</strong>
                    </div>
                </div>
                @else
                <div class="waktu-banner {{ $waktuAbsen['status'] === 'tutup' ? 'telat' : $waktuAbsen['status'] }}" style="{{ $waktuAbsen['status'] === 'tutup' ? 'background:#FEF3C7;border-color:#FDE68A;' : '' }}">
                    <div class="waktu-banner-icon" style="{{ $waktuAbsen['status'] === 'tutup' ? 'background:#F59E0B;' : '' }}">
                        <i class="fa-solid {{ $waktuAbsen['status'] === 'buka' ? 'fa-door-open' : ($waktuAbsen['status'] === 'tutup' ? 'fa-clock' : 'fa-clock') }}"></i>
                    </div>
                    <div class="waktu-banner-info">
                        <h3 id="waktuBannerTitle" style="{{ $waktuAbsen['status'] === 'tutup' ? 'color:#92400E;' : '' }}">
                            @if ($waktuAbsen['status'] === 'belum')
                            Absen Masuk belum dibuka.
                            @elseif ($waktuAbsen['status'] === 'buka')
                            Absen Masuk sedang dibuka.
                            @else
                            Absen Masuk Telat — masih dibuka.
                            @endif
                        </h3>
                        <p id="waktuBannerSub" style="{{ $waktuAbsen['status'] === 'tutup' ? 'color:#92400E;' : '' }}">
                            @if ($waktuAbsen['status'] === 'belum')
                            Absen Masuk dibuka pukul {{ $waktuAbsen['mulai'] }} WIB.
                            @elseif ($waktuAbsen['status'] === 'buka')
                            Silakan lengkapi foto dan GPS (jika Masuk), lalu tekan tombol Absen Masuk.
                            @else
                            Anda terlambat. Absen Masuk setelah pukul {{ $waktuAbsen['selesai'] }} WIB akan dicatat sebagai <strong>Telat</strong>.
                            @endif
                        </p>
                        <span class="waktu-countdown" id="waktuCountdown"></span>
                    </div>
                    <div class="waktu-banner-range">
                        <span class="wb-label">Jam Absen</span>
                        <strong>{{ $waktuAbsen['mulai'] }} - {{ $waktuAbsen['selesai'] }} WIB</strong>
                    </div>
                </div>
                @endif

                <!-- ===== SUDAH ABSEN ===== -->
                @if ($sudahAbsen)
                <div class="absen-done">
                    <div class="absen-done-icon">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <h3>Anda sudah melakukan absen masuk hari ini</h3>
                        <p>
                            Absensi tidak perlu diulang. Terima kasih!
                            @if ($absensiHariIni->status_kehadiran)
                                <br><strong>Status:</strong> {{ $absensiHariIni->status_kehadiran }}@if($absensiHariIni->keterangan) — {{ $absensiHariIni->keterangan }}@endif
                            @endif
                        </p>
                        @if ($absensiHariIni->foto)
                        <img src="{{ route('foto.lihat', $absensiHariIni->id) }}" alt="Foto Absen Masuk" class="foto-absen-sudah">
                        @endif
                    </div>
                    <div class="done-time">
                        <span class="dt-label">Jam masuk tercatat</span>
                        <span class="dt-value">{{ \Carbon\Carbon::parse($absensiHariIni->jam_masuk)->format('H:i') }}</span>
                        <span class="dt-label">Waktu Server (WIB)</span>
                    </div>
                </div>
                @endif

                @if (! $sudahAbsen)
                <form method="POST" action="{{ route('absen.masuk.store') }}" id="formAbsen" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="latitude" id="latField" value="{{ old('latitude') }}">
                    <input type="hidden" name="longitude" id="lngField" value="{{ old('longitude') }}">
                    <input type="hidden" name="accuracy" id="accuracyField" value="{{ old('accuracy') }}">
                    {{-- FIX: hapus capture="user" dan hidden -> style display:none agar DataTransfer inject tetap kompatibel di mobile Chrome/Safari --}}
                    <input type="file" name="foto" id="fotoField" accept="image/*" style="display:none">

                <!-- ===== PILIHAN JENIS KEHADIRAN ===== -->
                <div class="absen-card jenis-kehadiran-card">
                    <div class="absen-card-header">
                        <i class="fa-solid fa-list-check"></i>
                        <h3>Jenis Kehadiran</h3>
                    </div>
                    <div class="jenis-grid">
                        <label class="jenis-option {{ $oldStatus === 'Masuk' ? 'active' : '' }}" data-jenis="Masuk">
                            <input type="radio" name="status_kehadiran" value="Masuk" {{ $oldStatus === 'Masuk' ? 'checked' : '' }}>
                            <div class="jenis-icon"><i class="fa-solid fa-door-open"></i></div>
                            <strong>Masuk</strong>
                        </label>
                        <label class="jenis-option {{ $oldStatus === 'Izin' ? 'active' : '' }}" data-jenis="Izin">
                            <input type="radio" name="status_kehadiran" value="Izin" {{ $oldStatus === 'Izin' ? 'checked' : '' }}>
                            <div class="jenis-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
                            <strong>Izin</strong>
                        </label>
                        <label class="jenis-option {{ $oldStatus === 'Dispensasi' ? 'active' : '' }}" data-jenis="Dispensasi">
                            <input type="radio" name="status_kehadiran" value="Dispensasi" {{ $oldStatus === 'Dispensasi' ? 'checked' : '' }}>
                            <div class="jenis-icon"><i class="fa-solid fa-file-circle-check"></i></div>
                            <strong>Dispensasi</strong>
                        </label>
                        <label class="jenis-option {{ $oldStatus === 'Sakit' ? 'active' : '' }}" data-jenis="Sakit">
                            <input type="radio" name="status_kehadiran" value="Sakit" {{ $oldStatus === 'Sakit' ? 'checked' : '' }}>
                            <div class="jenis-icon"><i class="fa-solid fa-house-medical"></i></div>
                            <strong>Sakit</strong>
                        </label>
                    </div>

                    @php
                        $keteranganPlaceholders = [
                            'Masuk' => 'Contoh: Izin keperluan keluarga, Dispensasi lomba, Sakit demam...',
                            'Izin' => 'Contoh: izin kepentingan keluarga yang tidak bisa di tinggalkan',
                            'Dispensasi' => 'Contoh: dispensasi latihan dan lomba drumband',
                            'Sakit' => 'Contoh: sakit demam, batuk, badan lemas',
                        ];
                        $keteranganPlaceholderAwal = $keteranganPlaceholders[$oldStatus] ?? $keteranganPlaceholders['Masuk'];
                        $keteranganHelps = [
                            'Masuk' => 'Isi keterangan singkat untuk Izin / Dispensasi / Sakit jika diperlukan.',
                            'Izin' => 'Isi keterangan singkat untuk Izin jika diperlukan.',
                            'Dispensasi' => 'Isi keterangan singkat untuk Dispensasi jika diperlukan.',
                            'Sakit' => 'Isi keterangan singkat untuk Sakit jika diperlukan.',
                        ];
                        $keteranganHelpAwal = $keteranganHelps[$oldStatus] ?? $keteranganHelps['Masuk'];
                    @endphp
                    <div id="keteranganWrap" class="keterangan-wrap" style="{{ in_array($oldStatus, ['Izin','Dispensasi','Sakit']) ? '' : 'display:none' }}">
                        <label for="keteranganField" class="form-label" style="margin-top:14px;display:block;">Keterangan <span style="color:var(--text-sec);font-weight:400;">(opsional)</span></label>
                        <textarea name="keterangan" id="keteranganField" class="form-control" rows="3" placeholder="{{ $keteranganPlaceholderAwal }}">{{ old('keterangan') }}</textarea>
                        <p class="form-hint" id="keteranganHelp">{{ $keteranganHelpAwal }}</p>
                    </div>

                    <div id="buktiWrap" class="keterangan-wrap" style="{{ in_array($oldStatus, ['Izin','Dispensasi','Sakit']) ? '' : 'display:none' }}">
                        <label class="form-label" style="margin-bottom:8px;display:block;">Bukti Pendukung <span id="buktiLabelStatus">{{ in_array($oldStatus, ['Dispensasi','Sakit']) ? '(Wajib)' : '(Opsional)' }}</span> <span id="buktiWajibMark" style="{{ in_array($oldStatus, ['Dispensasi','Sakit']) ? 'color:#DC2626;' : 'display:none;color:#DC2626;' }}">*</span></label>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <input type="file" name="bukti_file" id="buktiFile" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" hidden>
                            <button type="button" class="cam-btn primary" id="btnPilihBukti"><i class="fa-solid fa-paperclip"></i> Pilih File / Foto</button>
                            <span id="buktiFileName" style="font-size:0.80rem;color:var(--text-sec);">Belum ada file dipilih</span>
                            <button type="button" class="cam-btn" id="btnHapusBukti" style="display:none;"><i class="fa-solid fa-xmark"></i> Hapus</button>
                        </div>
                        <p class="form-hint" id="buktiHint" style="margin-top:8px;">@if($oldStatus === 'Dispensasi') Bukti dispensasi wajib diupload. Format: JPG, JPEG, PNG, PDF. Maksimal 5 MB. @elseif($oldStatus === 'Sakit') Bukti sakit wajib diupload. Format: JPG, JPEG, PNG, PDF. Maksimal 5 MB. @else Upload bukti pendukung jika ada. File tidak wajib dilampirkan. Format: JPG, JPEG, PNG, PDF. Maksimal 5 MB. @endif</p>
                        <div id="buktiPreview" style="margin-top:10px; display:none;">
                            <img id="buktiPreviewImg" src="" alt="Preview Bukti" style="max-width:220px; max-height:220px; border-radius:10px; border:1px solid var(--border); display:none;">
                            <div id="buktiPreviewPdf" style="display:none; padding:10px 14px; background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; font-size:0.82rem; color:#991B1B;">
                                <i class="fa-solid fa-file-pdf"></i> <span id="buktiPdfName"></span>
                            </div>
                        </div>
                        <p id="buktiError" style="color:#DC2626; font-size:0.80rem; margin-top:8px; display:none;"></p>
                    </div>

                    <div id="gpsInfoMasuk" class="gps-info-box" style="{{ $oldStatus === 'Masuk' ? '' : 'display:none' }}">
                        <i class="fa-solid fa-location-dot"></i> Untuk <strong>Masuk</strong>, pastikan GPS aktif dan Anda berada di dalam radius kantor ({{ number_format($lokasi['radius'],0,',','.') }} m).
                    </div>
                    <div id="gpsInfoTanpa" class="gps-info-box tanpa" style="{{ $oldStatus !== 'Masuk' ? '' : 'display:none' }}">
                        <i class="fa-solid fa-circle-info"></i> Untuk <strong id="tanpaLabel">{{ $oldStatus !== 'Masuk' ? $oldStatus : 'Izin/Dispensasi/Sakit' }}</strong>, lokasi GPS tidak diperlukan dan tidak divalidasi.
                    </div>
                </div>

                <!-- ===== GRID KARTU ===== -->
                <div class="absen-grid" id="absenGrid">

                    <!-- Kartu Waktu -->
                    <div class="absen-card" id="waktuCard" style="{{ $oldStatus === 'Masuk' ? '' : 'display:none' }}">
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

                    <!-- Kartu Kamera / Selfie -->
                    <div class="absen-card" id="selfieCard" style="{{ $oldStatus === 'Masuk' ? '' : 'display:none' }}">
                        <div class="absen-card-header">
                            <i class="fa-solid fa-camera"></i>
                            <h3>Foto Selfie</h3>
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
                            Foto menjadi bukti Absen Masuk. <span id="fotoNoteMasuk" style="{{ $oldStatus === 'Masuk' ? '' : 'display:none' }}">@if (config('absensi.foto_wajib', true)) Foto wajib untuk Masuk. @endif</span><span id="fotoNoteTanpa" style="{{ $oldStatus !== 'Masuk' ? '' : 'display:none' }}">Foto opsional untuk {{ $oldStatus !== 'Masuk' ? $oldStatus : 'Izin/Dispensasi/Sakit' }}.</span>
                        </p>
                    </div>

                    <!-- Kartu Lokasi GPS (disembunyikan visual, tetap di DOM untuk JS GPS) -->
                    <div class="absen-card" id="lokasiCard" style="display:none">
                        <div class="absen-card-header">
                            <i class="fa-solid fa-location-dot"></i>
                            <h3>Lokasi GPS</h3>
                        </div>
                        <div class="loc-status state-loading" id="locStatus">
                            <span class="dot"></span><span>Mencari lokasi...</span>
                        </div>
                        <div class="loc-list">
                            <div class="loc-row"><span>Latitude</span><strong id="latValue">-</strong></div>
                            <div class="loc-row"><span>Longitude</span><strong id="lngValue">-</strong></div>
                            <div class="loc-row"><span>Akurasi</span><strong id="accuracyValue">-</strong></div>
                            <div class="loc-row"><span>Jarak ke Kantor</span><strong id="jarakValue">-</strong></div>
                        </div>
                        <span class="radius-pill wait" id="radiusStatus" style="margin-top:10px; display:inline-flex;">Menunggu GPS...</span>
                        <div id="lokasiTanpaGpsNote" style="display:none; margin-top:10px; font-size:0.80rem; color:var(--text-sec);"><i class="fa-solid fa-circle-info"></i> GPS tidak diperlukan untuk status ini.</div>
                        <!-- Panel Debug GPS (hanya diagnosis, disembunyikan untuk UI ringkas; logic GPS tetap berjalan) -->
                        <div id="gpsDebugPanel" style="display:none; margin-top:12px; padding:10px 12px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; font-size:0.78rem; line-height:1.5;">
                            <div style="font-weight:600; color:var(--text-main); margin-bottom:4px;"><i class="fa-solid fa-bug"></i> Debug GPS (diagnosis)</div>
                            <div>Latitude: <strong id="dbgLat">-</strong></div>
                            <div>Longitude: <strong id="dbgLng">-</strong></div>
                            <div>Akurasi: <strong id="dbgAcc">-</strong></div>
                            <div>Jarak ke Kantor: <strong id="dbgJarak">-</strong></div>
                            <div>Radius Kantor: <strong id="dbgRadius">-</strong> &nbsp;|&nbsp; Max Accuracy: <strong id="dbgMaxAcc">-</strong></div>
                            <div>Status: <strong id="dbgStatus">MENUNGGU GPS</strong></div>
                            <div style="margin-top:4px; color:var(--text-sec); font-size:0.72rem;">Jika akurasi &gt; maxAccuracy atau jarak &gt; radius → lokasi ditolak. Ubah radius di <em>Pengaturan Lokasi Kantor</em> (admin) otomatis terbaca di sini &amp; di backend.</div>
                        </div>
                    </div>

                </div>

                <!-- ===== TOMBOL ABSEN ===== -->
                <div class="absen-action">
                        <button type="submit" class="btn-absen" id="btnAbsen"
                                data-sudah-absen="0"
                                data-waktu-status="{{ $waktuAbsen['status'] }}">
                            <span class="btn-spinner"><i class="fa-solid fa-spinner fa-spin"></i></span>
                            <i class="fa-solid fa-location-dot" id="btnIcon"></i> <span id="btnText">Absen Masuk</span>
                        </button>
                    <p class="absen-hint" id="absenHint">
                        @if ($waktuAbsen['status'] === 'belum')
                        Tombol absen aktif pukul {{ $waktuAbsen['mulai'] }} WIB.
                        @elseif ($waktuAbsen['status'] === 'tutup')
                        Anda terlambat. Absen Masuk akan dicatat sebagai <strong>Telat</strong>. Silakan lengkapi foto dan GPS.
                        @else
                        Pilih jenis kehadiran terlebih dahulu.
                        @endif
                    </p>
                </div>

                </form>
                @else
                <!-- Jika sudah absen, tampilkan form dummy disabled agar layout tetap konsisten -->
                <div class="absen-action">
                    <button type="button" class="btn-absen" disabled>
                        <i class="fa-solid fa-circle-check"></i> Absen Masuk Selesai
                    </button>
                    <p class="absen-hint">Anda sudah tercatat masuk hari ini. Tombol absen dinonaktifkan.</p>
                </div>
                @endif
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
    <script src="{{ asset('js/absen.js') }}?v={{ @filemtime(public_path('js/absen.js')) }}"></script>
    @endif
</body>
</html>
