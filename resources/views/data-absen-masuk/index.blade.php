@php
    $cari           = $filters['cari'] ?? null;
    $tanggalDari    = $filters['tanggal_dari'] ?? null;
    $tanggalSampai  = $filters['tanggal_sampai'] ?? null;
    // fallback legacy (jika ada link lama ?tanggal=...)
    $tanggalFilter  = $tanggalDari ?? $filters['tanggal'] ?? null;
    $statusFilter   = $filters['status'] ?? null;

    $statusLabels = [
        'valid'       => '✓ Lokasi Valid',
        'tidak_valid' => '✕ Lokasi Tidak Valid',
        'tanpa'       => 'Tanpa Lokasi',
    ];
    $statusBadges = [
        'valid'       => 'badge-masuk',
        'tidak_valid' => 'badge-tidak',
        'tanpa'       => 'badge-tanpa',
    ];
    $kehadiranBadges = [
        'Masuk'       => 'badge-masuk',
        'Telat'       => 'badge-telat',
        'Tidak Masuk' => 'badge-tidak',
        'Izin'        => 'badge-izin',
        'Dispensasi'  => 'badge-dispensasi',
        'Sakit'       => 'badge-sakit',
        'Pulang'      => 'badge-pulang',
    ];
    $statusFilterLabels = [
        'masuk'  => 'Masuk',
        'telat'  => 'Telat',
        'sakit'  => 'Sakit',
        'dispen' => 'Dispen',
        'izin'   => 'Izin',
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Absen - Sistem Absensi PKL Sandya Network</title>
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
                <a href="{{ route('data-absen-masuk.index') }}" class="nav-link active" data-tooltip="Data Absen">
                    <i class="fa-solid fa-clipboard-check" style="color:#0F4C81"></i>
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
                            <a href="{{ route('data-absen-masuk.index') }}" class="dropdown-item"><i class="fa-solid fa-clipboard-check"></i> Data Absen</a>
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
                <div class="page-title">
                    <h1 class="page-title-main">Data Absen</h1>
                    <p class="page-title-sub">
                        Pantau riwayat absensi siswa — lengkap dengan foto, koordinat GPS,
                        Jarak dari Kantor, dan Radius Kantor saat absensi.
                    </p>
                </div>

                <!-- ===== INFO ABSEN HARI INI (WIB) ===== -->
                <div class="info-today">
                    <div class="info-today-icon">
                        <i class="fa-solid fa-calendar-day"></i>
                    </div>
                    <div class="info-today-text">
                        <h3>Absen Hari Ini</h3>
                        <p>
                            <strong data-jumlah-hari-ini="{{ $jumlahHariIni }}">{{ $jumlahHariIni }}</strong> siswa —
                            {{ \Carbon\Carbon::parse($tanggalHariIni)->translatedFormat('l, d F Y') }} (WIB)
                        </p>
                    </div>
                    <a href="{{ route('data-absen-masuk.index', ['tanggal' => $tanggalHariIni, 'tanggal_dari' => $tanggalHariIni, 'tanggal_sampai' => $tanggalHariIni]) }}" class="btn-tool btn-today">
                        <i class="fa-solid fa-filter"></i> Lihat Hari Ini
                    </a>
                </div>

                <!-- ===== FILTER DATA ===== -->
                <div class="card-table">
                    <div class="card-table-header">
                        <div class="ct-title">
                            <i class="fa-solid fa-filter"></i>
                                <div>
                                <h3>Filter Data</h3>
                                <p>Cari berdasarkan nama siswa, rentang tanggal absensi (WIB), atau Status.</p>
                            </div>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('data-absen-masuk.index') }}" class="filter-form">
                        @if ($errors->any())
                            <div class="alert alert-danger" style="margin:12px 22px 0 22px">
                                <ul style="margin:0;padding-left:18px">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="filter-fields">
                            <div class="search-wrap filter-cari">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" name="cari" value="{{ $cari }}" class="search-input"
                                       placeholder="Cari nama siswa...">
                                @if ($cari)
                                <a href="{{ route('data-absen-masuk.index', array_filter(['tanggal_dari' => $tanggalDari, 'tanggal_sampai' => $tanggalSampai, 'status' => $statusFilter])) }}" class="search-clear" aria-label="Bersihkan pencarian">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </a>
                                @endif
                            </div>

                            <div class="filter-field">
                                <label for="filterTanggalDari" class="form-label">Tanggal Dari</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="filterTanggalDari" name="tanggal_dari"
                                           value="{{ $tanggalDari }}">
                                    <i class="fa-solid fa-calendar input-icon"></i>
                                </div>
                            </div>

                            <div class="filter-field">
                                <label for="filterTanggalSampai" class="form-label">Tanggal Sampai</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="filterTanggalSampai" name="tanggal_sampai"
                                           value="{{ $tanggalSampai }}">
                                    <i class="fa-solid fa-calendar input-icon"></i>
                                </div>
                            </div>

                            <div class="filter-field">
                                <label for="filterStatus" class="form-label">Status</label>
                                <div class="input-group">
                                    <select class="form-control" id="filterStatus" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="masuk" {{ $statusFilter === 'masuk' ? 'selected' : '' }}>Masuk</option>
                                        <option value="telat" {{ $statusFilter === 'telat' ? 'selected' : '' }}>Telat</option>
                                        <option value="sakit" {{ $statusFilter === 'sakit' ? 'selected' : '' }}>Sakit</option>
                                        <option value="dispen" {{ $statusFilter === 'dispen' ? 'selected' : '' }}>Dispen</option>
                                        <option value="izin" {{ $statusFilter === 'izin' ? 'selected' : '' }}>Izin</option>
                                    </select>
                                    <i class="fa-solid fa-clipboard-check input-icon"></i>
                                </div>
                            </div>

                            <div class="filter-buttons">
                                <button type="submit" class="btn-tool btn-search">
                                    <i class="fa-solid fa-magnifying-glass"></i> Cari
                                </button>
                                <a href="{{ route('data-absen-masuk.index') }}" class="btn-tool">
                                    <i class="fa-solid fa-rotate"></i> Reset
                                </a>
                                <a href="{{ route('data-absen-masuk.print', request()->except('page')) }}" target="_blank" class="btn-tool">
                                    <i class="fa-solid fa-print"></i> Print
                                </a>
                                <a href="{{ route('data-absen-masuk.export.excel', request()->except('page')) }}" class="btn-tool btn-excel">
                                    <i class="fa-solid fa-file-excel"></i> Excel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ===== TABEL DATA ABSEN ===== -->
                <div class="card-table" style="margin-top:20px">
                    <div class="card-table-header">
                        <div class="ct-title">
                            <i class="fa-solid fa-clipboard-check"></i>
                            <div>
                                <h3>Riwayat Absen</h3>
                                <p>Data terbaru berada di atas. Klik foto untuk memperbesar, klik "Lihat Detail" untuk info lengkap.</p>
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="activity-table data-absen-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>User ID</th>
                                    <th>Nama Siswa</th>
                                    <th>Tanggal</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Pulang</th>
                                    <th>Jenis Kehadiran</th>
                                    <th>Bukti Pendukung</th>
                                    <th>Foto Masuk</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $row)
                                <tr>
                                    <td>{{ $items->firstItem() + $loop->index }}</td>
                                    <td style="font-family:monospace; white-space:nowrap; font-size:0.80rem;">{{ $row->user?->user_id ?? '-' }}</td>
                                    <td><div class="cell-nama">{{ $row->nama_tampil }}</div></td>
                                    <td>{{ $row->tgl_masuk->format('d-m-Y') }}</td>
                                    <td>{{ $row->jam_masuk }}</td>
                                    @php $isExemptMasuk = in_array($row->status_kehadiran_label, ['Izin','Dispensasi','Sakit'], true); @endphp
                                    <td>
                                        @if ($row->jam_pulang)
                                            {{ $row->jam_pulang }}
                                        @elseif ($isExemptMasuk)
                                            <span style="font-size:0.75rem;color:#10B981;font-weight:600" title="Tidak perlu Absen Pulang">Tidak Diperlukan</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge-status {{ $kehadiranBadges[$row->status_kehadiran_label] ?? 'badge-masuk' }}">
                                            {{ $row->status_kehadiran_label }}
                                        </span>
                                        @if(!empty($row->keterangan))
                                            <div style="font-size:0.7rem;color:var(--text-sec);margin-top:4px;max-width:160px;white-space:normal;line-height:1.4;">{{ \Illuminate\Support\Str::limit($row->keterangan, 30) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row->bukti_file)
                                        <a href="{{ route('bukti.lihat', $row->id) }}" target="_blank" class="btn-tool" style="font-size:0.78rem; padding:6px 10px; white-space:nowrap;">
                                            <i class="fa-solid fa-paperclip"></i> Lihat Bukti
                                        </a>
                                        <div style="font-size:0.68rem;color:var(--text-sec);margin-top:4px;max-width:140px;white-space:normal;overflow:hidden;text-overflow:ellipsis;">{{ $row->bukti_original_name ?? basename($row->bukti_file) }}</div>
                                        @else
                                        <span class="foto-absensi-empty">Tidak ada bukti</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row->foto)
                                        <img src="{{ route('foto.lihat', $row->id) }}" alt="Foto Absen {{ $row->nama_tampil }}"
                                             class="foto-absensi foto-lightbox" data-lightbox-img="{{ route('foto.lihat', $row->id) }}"
                                             title="Klik untuk memperbesar">
                                        <span style="display:none">storage/{{ $row->foto }}</span>
                                        @else
                                        <span class="foto-absensi-empty">Foto tidak tersedia</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php $displayStatus = $row->status_kehadiran_label === 'Dispensasi' ? 'Dispen' : $row->status_kehadiran_label; @endphp
                                        <span class="badge-status {{ $kehadiranBadges[$row->status_kehadiran_label] ?? 'badge-masuk' }}"
                                              data-status="{{ $row->status_kehadiran_label }}">
                                            {{ $displayStatus }}
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-tool btn-detail" data-detail-id="{{ $row->id }}">
                                            <i class="fa-solid fa-eye"></i> Lihat Detail
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11">
                                        <div class="table-empty">
                                            <i class="fa-solid fa-inbox"></i>
                                            <p>Tidak ada data Absen yang cocok.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer">
                        <span>Menampilkan <strong>{{ $items->total() }}</strong> data Absen</span>
                        <div class="pagination-links">
                            {{ $items->links('vendor.pagination.custom') }}
                        </div>
                    </div>
                </div>

                {{-- Tidak Masuk sekarang digabung ke tabel utama Data Absen (virtual, tanpa duplikat) --}}
                @if(isset($targetTidakMasukTanggal) && $targetTidakMasukTanggal && isset($tidakMasukUsers) && $tidakMasukUsers->count() > 0)
                    <div class="card-table" style="margin-top:20px; border-color:#FECACA;">
                        <div class="card-table-header" style="background:#FEF2F2;">
                            <div class="ct-title">
                                <i class="fa-solid fa-user-xmark" style="color:#DC2626;"></i>
                                <div>
                                    <h3 style="color:#991B1B;">Tidak Masuk — {{ \Carbon\Carbon::parse($targetTidakMasukTanggal)->translatedFormat('d F Y') }}</h3>
                                    <p style="color:#B91C1C;">Siswa aktif tanpa absensi pada tanggal ini (dihitung setelah {{ \App\Support\WaktuAbsen::mulai('pulang') }} WIB). Data sudah digabung ke tabel utama — total <strong>{{ $items->total() }}</strong> siswa ditampilkan (termasuk <strong>{{ $tidakMasukUsers->count() }}</strong> Tidak Masuk).</p>
                                </div>
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

    <!-- ===== LIGHTBOX FOTO ===== -->
    <div class="modal-overlay" id="lightboxOverlay" aria-hidden="true">
        <div class="modal-lightbox">
            <button type="button" class="modal-close" id="lightboxClose" aria-label="Tutup foto">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <img id="lightboxImg" src="" alt="Foto Absensi">
            <p class="lightbox-caption">Foto Absensi</p>
        </div>
    </div>

    <!-- ===== MODAL DETAIL ABSEN ===== -->
    <div class="modal-overlay" id="detailOverlay" aria-hidden="true">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3>Detail Absen</h3>
                <button type="button" class="modal-close" id="detailClose" aria-label="Tutup detail">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="detail-hero">
                <div class="detail-hero-photo">
                    <img id="detailFotoImg" src="" alt="Foto Selfie Masuk" hidden>
                    <span id="detailFotoEmpty" class="detail-foto-empty" hidden>Foto tidak tersedia</span>
                </div>
                <div class="detail-hero-info">
                    <span class="detail-role-label">Nama Siswa</span>
                    <h4 id="detailNama">-</h4>
                    <div class="detail-meta">
                        <span><i class="fa-solid fa-calendar"></i> <span id="detailTanggal">-</span></span>
                        <span><i class="fa-solid fa-clock"></i> <span id="detailHari">-</span></span>
                        <span><i class="fa-solid fa-right-to-bracket"></i> <span id="detailJam">-</span><span id="detailJamWib"> WIB</span></span>
                        <span><i class="fa-solid fa-right-from-bracket"></i> <span id="detailJamPulang">-</span><span id="detailJamPulangWib"> WIB</span></span>
                    </div>
                    <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        <span class="badge-status badge-masuk" id="detailKehadiranBadge">-</span>
                        <span id="detailKeterangan" style="font-size:0.8rem;color:var(--text-sec);max-width:260px;"></span>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h5><i class="fa-solid fa-paperclip"></i> Bukti Pendukung</h5>
                <div id="detailBuktiAda" style="display:none;">
                    <a id="detailBuktiLink" href="#" target="_blank" class="btn-tool" style="display:inline-flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-paperclip"></i> Lihat Bukti
                    </a>
                    <span id="detailBuktiNama" style="margin-left:10px; font-size:0.82rem; color:var(--text-sec);"></span>
                    <div id="detailBuktiPreview" style="margin-top:12px;">
                        <img id="detailBuktiImg" src="" alt="Bukti" style="max-width:100%; max-height:240px; border-radius:10px; border:1px solid var(--border); display:none;">
                        <div id="detailBuktiPdfInfo" style="display:none; padding:10px 14px; background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; font-size:0.82rem; color:#991B1B;">
                            <i class="fa-solid fa-file-pdf"></i> <span id="detailBuktiPdfName"></span>
                        </div>
                    </div>
                </div>
                <div id="detailBuktiEmpty" style="font-size:0.82rem; color:var(--text-sec);">Tidak ada bukti</div>
            </div>

            <div class="detail-section">
                <h5><i class="fa-solid fa-location-crosshairs"></i> Informasi Lokasi</h5>
                <div class="detail-grid">
                    <div class="detail-row">
                        <span class="detail-label">Latitude Siswa</span>
                        <span class="detail-value" id="detailLat">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Longitude Siswa</span>
                        <span class="detail-value" id="detailLng">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Akurasi GPS</span>
                        <span class="detail-value" id="detailAkurasi">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Jarak dari Kantor</span>
                        <span class="detail-value" id="detailJarak">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Radius Kantor</span>
                        <span class="detail-value" id="detailRadius">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value"><span class="badge-status badge-masuk" id="detailStatus">-</span></span>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h5><i class="fa-solid fa-building"></i> Informasi Kantor</h5>
                <div class="detail-kantor">
                    <strong id="kantorNama">-</strong>
                    <span id="kantorAlamat">-</span>
                    <span class="kantor-coords" id="kantorKoordinat">-</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" id="detailTutup">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        window.ABSEN_DETAILS = @json($details);

        (function () {
            var details = window.ABSEN_DETAILS || {};

            function rupiahAngka(v, angka) {
                if (v === null || v === undefined || v === '') return '-';
                return Number(v).toLocaleString('id-ID', { maximumFractionDigits: angka, minimumFractionDigits: 0 });
            }

            function statusBadge(status) {
                if (status === 'valid') return { text: '✓ Lokasi Valid', cls: 'badge-masuk' };
                if (status === 'tidak_valid') return { text: '✕ Lokasi Tidak Valid', cls: 'badge-tidak' };
                return { text: 'Tanpa Lokasi', cls: 'badge-tanpa' };
            }

            // ---- Lightbox foto ----
            var lightboxOverlay = document.getElementById('lightboxOverlay');
            var lightboxImg = document.getElementById('lightboxImg');

            document.querySelectorAll('.foto-lightbox').forEach(function (img) {
                img.addEventListener('click', function () {
                    lightboxImg.src = img.dataset.lightboxImg;
                    lightboxOverlay.classList.add('show');
                    lightboxOverlay.setAttribute('aria-hidden', 'false');
                });
            });

            document.querySelectorAll('.foto-lightbox-detail').forEach(function (img) {
                img.addEventListener('click', function () {
                    if (!img.dataset.lightboxImg) return;
                    lightboxImg.src = img.dataset.lightboxImg;
                    lightboxOverlay.classList.add('show');
                    lightboxOverlay.setAttribute('aria-hidden', 'false');
                });
            });

            function closeLightbox() {
                lightboxOverlay.classList.remove('show');
                lightboxOverlay.setAttribute('aria-hidden', 'true');
                lightboxImg.src = '';
            }
            document.getElementById('lightboxClose').addEventListener('click', closeLightbox);
            lightboxOverlay.addEventListener('click', function (e) {
                if (e.target === lightboxOverlay) closeLightbox();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { closeLightbox(); closeDetail(); }
            });

            // ---- Modal detail ----
            var detailOverlay = document.getElementById('detailOverlay');

            function closeDetail() {
                detailOverlay.classList.remove('show');
                detailOverlay.setAttribute('aria-hidden', 'true');
            }

            document.querySelectorAll('.btn-detail').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var d = details[btn.dataset.detailId];
                    if (!d) return;

                    document.getElementById('detailNama').textContent = d.nama;
                    document.getElementById('detailTanggal').textContent = d.tanggal;
                    document.getElementById('detailHari').textContent = d.hari;
                    document.getElementById('detailJam').textContent = d.jam_masuk;
                    document.getElementById('detailJamPulang').textContent = d.jam_pulang;

                    // Jenis kehadiran & keterangan
                    var kehadiranBadgeMap = {
                        'Masuk': 'badge-masuk',
                        'Izin': 'badge-izin',
                        'Dispensasi': 'badge-dispensasi',
                        'Sakit': 'badge-sakit',
                        'Pulang': 'badge-pulang'
                    };
                    var kehadiranEl = document.getElementById('detailKehadiranBadge');
                    var ketEl = document.getElementById('detailKeterangan');
                    if (kehadiranEl) {
                        var kLabel = d.status_kehadiran || '-';
                        kehadiranEl.textContent = kLabel;
                        kehadiranEl.className = 'badge-status ' + (kehadiranBadgeMap[kLabel] || 'badge-masuk');
                    }
                    if (ketEl) {
                        ketEl.textContent = d.keterangan ? ('"' + d.keterangan + '"') : '';
                    }

                    var fotoImg = document.getElementById('detailFotoImg');
                    var fotoEmpty = document.getElementById('detailFotoEmpty');
                    if (d.foto) {
                        fotoImg.src = d.foto; fotoImg.hidden = false; fotoImg.style.display='';
                        fotoEmpty.hidden = true; fotoEmpty.style.display='none';
                        fotoImg.onerror=function(){ fotoImg.hidden=true; fotoImg.style.display='none'; fotoEmpty.hidden=false; fotoEmpty.style.display=''; };
                        fotoImg.onload=function(){ fotoEmpty.hidden=true; fotoEmpty.style.display='none'; };
                    } else {
                        fotoImg.hidden = true; fotoImg.style.display='none'; fotoImg.src = '';
                        fotoEmpty.hidden = false; fotoEmpty.style.display='';
                    }

                    // Bukti pendukung
                    var buktiAda = document.getElementById('detailBuktiAda');
                    var buktiEmpty = document.getElementById('detailBuktiEmpty');
                    var buktiLink = document.getElementById('detailBuktiLink');
                    var buktiNama = document.getElementById('detailBuktiNama');
                    var buktiImg = document.getElementById('detailBuktiImg');
                    var buktiPdfInfo = document.getElementById('detailBuktiPdfInfo');
                    var buktiPdfName = document.getElementById('detailBuktiPdfName');
                    if (d.bukti_file) {
                        if (buktiAda) buktiAda.style.display = 'block';
                        if (buktiEmpty) buktiEmpty.style.display = 'none';
                        if (buktiLink) buktiLink.href = d.bukti_file;
                        if (buktiNama) buktiNama.textContent = d.bukti_original_name || d.bukti_file.split('/').pop();
                        var mime = (d.bukti_mime_type || '').toLowerCase();
                        var isPdf = mime.includes('pdf') || (d.bukti_original_name && d.bukti_original_name.toLowerCase().endsWith('.pdf'));
                        if (isPdf) {
                            if (buktiImg) buktiImg.style.display = 'none';
                            if (buktiPdfInfo) buktiPdfInfo.style.display = 'block';
                            if (buktiPdfName) buktiPdfName.textContent = d.bukti_original_name || 'Dokumen PDF';
                        } else {
                            if (buktiImg) { buktiImg.src = d.bukti_file; buktiImg.style.display = 'block'; }
                            if (buktiPdfInfo) buktiPdfInfo.style.display = 'none';
                        }
                    } else {
                        if (buktiAda) buktiAda.style.display = 'none';
                        if (buktiEmpty) buktiEmpty.style.display = 'block';
                        if (buktiImg) { buktiImg.style.display='none'; buktiImg.src=''; }
                        if (buktiPdfInfo) buktiPdfInfo.style.display='none';
                    }

                    document.getElementById('detailLat').textContent = d.latitude !== null ? rupiahAngka(d.latitude, 6) : '-';
                    document.getElementById('detailLng').textContent = d.longitude !== null ? rupiahAngka(d.longitude, 6) : '-';
                    document.getElementById('detailAkurasi').textContent = d.accuracy !== null ? '±' + rupiahAngka(d.accuracy, 1) + ' m' : '-';
                    document.getElementById('detailJarak').textContent = d.jarak !== null ? rupiahAngka(d.jarak, 2) + ' meter' : '-';
                    document.getElementById('detailRadius').textContent = d.radius !== null ? rupiahAngka(d.radius, 0) + ' meter' : '-';

                    var badge = statusBadge(d.status_lokasi);
                    var statusEl = document.getElementById('detailStatus');
                    statusEl.textContent = badge.text;
                    statusEl.className = 'badge-status ' + badge.cls;

                    document.getElementById('kantorNama').textContent = d.kantor.nama;
                    document.getElementById('kantorAlamat').textContent = d.kantor.alamat;
                    document.getElementById('kantorKoordinat').textContent =
                        d.kantor.latitude !== null
                            ? 'Latitude: ' + rupiahAngka(d.kantor.latitude, 6) + ' • Longitude: ' + rupiahAngka(d.kantor.longitude, 6)
                            : '';

                    detailOverlay.classList.add('show');
                    detailOverlay.setAttribute('aria-hidden', 'false');
                });
            });

            document.getElementById('detailClose').addEventListener('click', closeDetail);
            document.getElementById('detailTutup').addEventListener('click', closeDetail);
            detailOverlay.addEventListener('click', function (e) {
                if (e.target === detailOverlay) closeDetail();
            });
        })();
    </script>

    <script src="{{ asset('js/dashboard.js') }}"></script>
    @if(auth()->check() && auth()->user()->role === 'admin')
    <script src="{{ asset('js/admin-notifikasi.js') }}"></script>
    @endif
</body>
</html>