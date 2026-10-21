<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Lokasi Kantor - Sistem Absensi PKL Sandya Network</title>
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
                <a href="{{ route('users.index') }}" class="nav-link" data-tooltip="Menu User">
                    <i class="fa-solid fa-users"></i>
                    <span>Menu User</span>
                </a>
                <a href="{{ route('lokasi-kantor.index') }}" class="nav-link active" data-tooltip="Pengaturan Lokasi Kantor">
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
                <div class="page-title">
                    <h1 class="page-title-main">Pengaturan Lokasi Kantor</h1>
                    <p class="page-title-sub">
                        Atur nama kantor, alamat, koordinat Lokasi Kantor, Radius Kantor, Jam Absen Masuk, dan Jam Absen Pulang yang digunakan validasi absensi.
                    </p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success" id="flashSuccess">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger" id="flashError">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger" id="flashError">
                        <ul style="margin:0;padding-left:18px">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- ===== FORM PENGATURAN - 5 SECTION TERSTRUKTUR ===== -->
                <form method="POST" action="{{ route('lokasi-kantor.update') }}">
                    @csrf
                    <div class="lokasi-sections">

                        <!-- 1. INFORMASI KANTOR -->
                        <div class="card-table">
                            <div class="card-table-header">
                                <div class="ct-title">
                                    <i class="fa-solid fa-building"></i>
                                    <div>
                                        <h3>Informasi Kantor</h3>
                                        <p>Identitas kantor yang ditampilkan pada sistem.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="lokasi-card-body">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="nama_kantor" class="form-label">Nama Kantor</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nama_kantor" name="nama_kantor"
                                                   value="{{ old('nama_kantor', $lokasi['nama']) }}"
                                                   placeholder="Nama kantor" required maxlength="200">
                                            <i class="fa-solid fa-building input-icon"></i>
                                        </div>
                                    </div>
                                    <div class="form-group" style="grid-column:1/-1">
                                        <label for="alamat_kantor" class="form-label">Alamat Kantor</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="alamat_kantor" name="alamat_kantor"
                                                   value="{{ old('alamat_kantor', $lokasi['alamat']) }}"
                                                   placeholder="Alamat lengkap kantor" required maxlength="500">
                                            <i class="fa-solid fa-map-location-dot input-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. PENGATURAN LOKASI -->
                        <div class="card-table">
                            <div class="card-table-header">
                                <div class="ct-title">
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                    <div>
                                        <h3>Pengaturan Lokasi</h3>
                                        <p>Koordinat dan radius untuk validasi GPS absensi.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="lokasi-card-body">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="latitude" class="form-label">Latitude Kantor</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="latitude" name="latitude"
                                                   value="{{ old('latitude', number_format($lokasi['latitude'], 6, '.', '')) }}"
                                                   placeholder="Contoh: -8.208000" required>
                                            <i class="fa-solid fa-globe input-icon"></i>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="longitude" class="form-label">Longitude Kantor</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="longitude" name="longitude"
                                                   value="{{ old('longitude', number_format($lokasi['longitude'], 6, '.', '')) }}"
                                                   placeholder="Contoh: 111.100000" required>
                                            <i class="fa-solid fa-globe input-icon"></i>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="radius_kantor" class="form-label">Radius Kantor (meter)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="radius_kantor" name="radius_kantor"
                                                   value="{{ old('radius_kantor', (int) $lokasi['radius']) }}"
                                                   placeholder="Contoh: 100" min="10" max="50000" required>
                                            <i class="fa-solid fa-bullseye input-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. PENGATURAN ABSEN MASUK -->
                        <div class="card-table">
                            <div class="card-table-header">
                                <div class="ct-title">
                                    <i class="fa-solid fa-arrow-right-to-bracket" style="color:#10B981"></i>
                                    <div>
                                        <h3>Pengaturan Absen Masuk</h3>
                                        <p>Jendela waktu absen masuk (WIB).</p>
                                    </div>
                                </div>
                            </div>
                            <div class="lokasi-card-body">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="jam_mulai" class="form-label">Jam Mulai Absen Masuk (WIB)</label>
                                        <div class="input-group">
                                            <input type="time" class="form-control" id="jam_mulai" name="jam_mulai"
                                                   value="{{ old('jam_mulai', $jamAbsen['mulai']) }}" required>
                                            <i class="fa-solid fa-hourglass-start input-icon"></i>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="jam_selesai" class="form-label">Jam Selesai Absen Masuk (WIB)</label>
                                        <div class="input-group">
                                            <input type="time" class="form-control" id="jam_selesai" name="jam_selesai"
                                                   value="{{ old('jam_selesai', $jamAbsen['selesai']) }}" required>
                                            <i class="fa-solid fa-hourglass-end input-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. PENGATURAN ABSEN PULANG -->
                        <div class="card-table">
                            <div class="card-table-header">
                                <div class="ct-title">
                                    <i class="fa-solid fa-arrow-right-from-bracket" style="color:#F87171"></i>
                                    <div>
                                        <h3>Pengaturan Absen Pulang</h3>
                                        <p>Jendela waktu absen pulang (WIB).</p>
                                    </div>
                                </div>
                            </div>
                            <div class="lokasi-card-body">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="jam_pulang_mulai" class="form-label">Jam Pulang Mulai (WIB)</label>
                                        <div class="input-group">
                                            <input type="time" class="form-control" id="jam_pulang_mulai" name="jam_pulang_mulai"
                                                   value="{{ old('jam_pulang_mulai', $jamPulang['mulai']) }}" required>
                                            <i class="fa-solid fa-hourglass-start input-icon"></i>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="jam_pulang_selesai" class="form-label">Jam Pulang Selesai (WIB)</label>
                                        <div class="input-group">
                                            <input type="time" class="form-control" id="jam_pulang_selesai" name="jam_pulang_selesai"
                                                   value="{{ old('jam_pulang_selesai', $jamPulang['selesai']) }}" required>
                                            <i class="fa-solid fa-hourglass-end input-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. PENGATURAN LAPORAN -->
                        <div class="card-table">
                            <div class="card-table-header">
                                <div class="ct-title">
                                    <i class="fa-solid fa-file-lines"></i>
                                    <div>
                                        <h3>Pengaturan Laporan</h3>
                                        <p>Jendela waktu pengumpulan laporan Teknisi &amp; Sales (WIB).</p>
                                    </div>
                                </div>
                            </div>
                            <div class="lokasi-card-body">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="jam_laporan_mulai" class="form-label">Jam Laporan Mulai (WIB) <span style="font-weight:400; color:#6B7280; font-size:0.75rem;">(Teknisi &amp; Sales)</span></label>
                                        <div class="input-group">
                                            <input type="time" class="form-control" id="jam_laporan_mulai" name="jam_laporan_mulai"
                                                   value="{{ old('jam_laporan_mulai', $jamLaporan['mulai'] ?? '08:00') }}">
                                            <i class="fa-solid fa-file-lines input-icon"></i>
                                        </div>
                                        <small style="font-size:0.72rem; color:#6B7280; margin-top:4px; display:block;">Default 08:00. Sebelum jam ini laporan ditolak.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="jam_laporan_selesai" class="form-label">Jam Laporan Selesai (WIB) <span style="font-weight:400; color:#6B7280; font-size:0.75rem;">(Teknisi &amp; Sales)</span></label>
                                        <div class="input-group">
                                            <input type="time" class="form-control" id="jam_laporan_selesai" name="jam_laporan_selesai"
                                                   value="{{ old('jam_laporan_selesai', $jamLaporan['selesai'] ?? '23:59') }}">
                                            <i class="fa-solid fa-hourglass-end input-icon"></i>
                                        </div>
                                        <small style="font-size:0.72rem; color:#6B7280; margin-top:4px; display:block;">Default 23:59. Sampai jam ini laporan masih diterima.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOMBOL SIMPAN -->
                        <div class="card-table">
                            <div class="form-actions" style="margin-top:0; border-top:none; padding:18px 22px;">
                                <a href="{{ route('dashboard') }}" class="btn-modal-cancel">Kembali ke Dashboard</a>
                                <button type="submit" class="btn-modal-submit">
                                    <i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan
                                </button>
                            </div>
                        </div>

                    </div>
                </form>

                <!-- ===== INFO CATATAN (disembunyikan, masih fungsi untuk referensi) ===== -->
                <div class="card-table" style="margin-top:20px; display:none" hidden>
                    <div class="card-table-header">
                        <div class="ct-title">
                            <i class="fa-solid fa-circle-info"></i>
                            <div>
                                <h3>Catatan</h3>
                                <p>Bagaimana koordinat dipakai pada validasi absensi.</p>
                            </div>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="activity-table activity-table-kantor">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Nama Kantor</td>
                                    <td>{{ $lokasi['nama'] }}</td>
                                </tr>
                                <tr>
                                    <td>Alamat Kantor</td>
                                    <td>{{ $lokasi['alamat'] }}</td>
                                </tr>
                                <tr>
                                    <td>Latitude Kantor</td>
                                    <td>{{ number_format($lokasi['latitude'], 6, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Longitude Kantor</td>
                                    <td>{{ number_format($lokasi['longitude'], 6, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Radius Kantor</td>
                                    <td>{{ number_format($lokasi['radius'], 0, ',', '.') }} meter</td>
                                </tr>
                                <tr>
                                    <td>Jam Absen Masuk</td>
                                    <td>{{ $jamAbsen['mulai'] }} - {{ $jamAbsen['selesai'] }} WIB</td>
                                </tr>
                                <tr>
                                    <td>Absen Pulang</td>
                                    <td>{{ $jamPulang['mulai'] }} - {{ $jamPulang['selesai'] }} WIB</td>
                                </tr>
                            </tbody>
                        </table>
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