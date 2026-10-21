<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User - Sistem Absensi PKL Sandya Network</title>
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
                    <i class="fa-solid fa-house"></i><span>Dashboard</span>
                </a>
                <a href="{{ route('absen.masuk') }}" class="nav-link" data-tooltip="Absen Masuk">
                    <i class="fa-solid fa-arrow-right-to-bracket" style="color:#10B981"></i><span>Absen Masuk</span>
                </a>
                <a href="{{ route('absen.pulang') }}" class="nav-link" data-tooltip="Absen Pulang">
                    <i class="fa-solid fa-arrow-right-from-bracket" style="color:#F87171"></i><span>Absen Pulang</span>
                </a>
                <a href="{{ route('laporan.index') }}" class="nav-link" data-tooltip="Laporan">
                    <i class="fa-solid fa-file-lines"></i><span>Laporan</span>
                </a>
                @if (($role ?? $userSession['role'] ?? 'siswa') !== 'admin')
                <a href="{{ route('rekap.siswa') }}" class="nav-link" data-tooltip="Riwayat Rekap"><i class="fa-solid fa-chart-column"></i><span>Riwayat Rekap</span></a>
                @endif

                <p class="nav-section">Lainnya</p>
                <a href="{{ route('data-absen-masuk.index') }}" class="nav-link" data-tooltip="Data Absen">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Data Absen</span>
                </a>
                <a href="{{ route('rekap.admin') }}" class="nav-link" data-tooltip="Rekap Absen"><i class="fa-solid fa-chart-column"></i><span>Rekap Absen</span></a>
                <a href="{{ route('users.index') }}" class="nav-link active" data-tooltip="Menu User">
                    <i class="fa-solid fa-users"></i><span>Menu User</span>
                </a>
                <a href="{{ route('lokasi-kantor.index') }}" class="nav-link" data-tooltip="Pengaturan Lokasi Kantor"><i class="fa-solid fa-location-dot"></i><span>Pengaturan Lokasi</span></a>
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

        <!-- ============ MAIN ============ -->
        <div class="main-wrapper">

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
                        <i class="fa-regular fa-bell"></i><span class="badge-dot"></span>
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

            <main class="page-content">
                <div class="page-title">
                    <h1 class="page-title-main">Tambah User</h1>
                    <p class="page-title-sub">Lengkapi data akun dan profil siswa PKL di bawah ini.</p>
                </div>

                <div class="form-card">
                    <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
                        @csrf

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nama" class="form-label">Nama</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="nama" name="nama" value="{{ old('nama') }}" placeholder="Nama lengkap" required>
                                    <i class="fa-solid fa-user input-icon"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" placeholder="Username unik" required>
                                    <i class="fa-solid fa-at input-icon"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="email@contoh.com" required>
                                    <i class="fa-solid fa-envelope input-icon"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 8 karakter" required minlength="8">
                                    <i class="fa-solid fa-lock input-icon"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password" required minlength="8">
                                    <i class="fa-solid fa-lock input-icon"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="" disabled {{ old('role') ? '' : 'selected' }}>Pilih role</option>
                                    <option value="siswa" {{ old('role') === 'siswa' ? 'selected' : '' }}>Siswa</option>
                                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>

                            <div class="form-group" id="fieldUserId" style="display: none;">
                                <label for="user_id" class="form-label">User ID <span style="color:#DC2626;">*</span> <span style="font-weight:400; color:#6B7280; font-size:0.78rem;">(wajib untuk Siswa)</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="user_id" name="user_id" value="{{ old('user_id', $nextUserId ?? '') }}" placeholder="G1 - INT0043" maxlength="30" pattern="G1\s*-\s*INT\d{4,}">
                                    <i class="fa-solid fa-id-badge input-icon"></i>
                                </div>
                                <small style="font-size:0.72rem;color:#6B7280; margin-top:4px; display:block;">Format: <code>G1 - INT0030</code>. Harus unik. Tombol otomatis pakai nomor berikutnya.</small>
                                <button type="button" id="btnGenerateUserId" style="margin-top:6px; font-size:0.75rem; padding:4px 8px; border:1px solid #E5E7EB; border-radius:6px; background:#F8FAFC; color:#0F4C81; cursor:pointer; display:inline-flex; align-items:center; gap:6px;"><i class="fa-solid fa-wand-magic-sparkles"></i> Isi otomatis: {{ $nextUserId ?? 'G1 - INT...' }}</button>
                                @error('user_id')<div style="font-size:0.75rem;color:#DC2626;margin-top:4px;">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="" disabled {{ old('status') ? '' : 'selected' }}>Pilih status</option>
                                    <option value="Aktif" {{ old('status') === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                    <option value="Nonaktif" {{ old('status') === 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        @include('users._profil-siswa', ['isEdit' => false])

                        <div class="form-actions">
                            <a href="{{ route('users.index') }}" class="btn-modal-cancel">Batal</a>
                            <button type="submit" class="btn-modal-submit">
                                <i class="fa-solid fa-user-plus"></i> Simpan User
                            </button>
                        </div>
                    </form>
                </div>

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
    <script>
        (function(){
            const roleEl = document.getElementById('role');
            const fieldUserId = document.getElementById('fieldUserId');
            const inputUserId = document.getElementById('user_id');
            const btnGenerate = document.getElementById('btnGenerateUserId');
            const nextUserId = @json($nextUserId ?? null);
            const profilSiswa = document.getElementById('profilSiswa');
            const profilFields = profilSiswa ? profilSiswa.querySelectorAll('input, select, textarea') : [];
            function toggleUserId(){
                const role = roleEl ? roleEl.value : '';
                if(role === 'siswa'){
                    if(fieldUserId) fieldUserId.style.display = '';
                    if(inputUserId) {
                        inputUserId.required = true;
                        inputUserId.disabled = false;
                        // jika kosong, isi otomatis next
                        if(!inputUserId.value && nextUserId){
                            inputUserId.value = nextUserId;
                        }
                    }
                } else if(role === 'admin'){
                    if(fieldUserId) fieldUserId.style.display = 'none';
                    if(inputUserId) {
                        inputUserId.required = false;
                        inputUserId.disabled = true;
                        inputUserId.value = '';
                    }
                } else {
                    if(fieldUserId) fieldUserId.style.display = 'none';
                    if(inputUserId) inputUserId.required = false;
                }
            }
            function toggleProfilSiswa(){
                const isSiswa = roleEl && roleEl.value === 'siswa';
                if(profilSiswa) profilSiswa.style.display = isSiswa ? '' : 'none';
                profilFields.forEach(function(el){
                    el.disabled = !isSiswa;
                    el.required = isSiswa;
                });
            }
            if(roleEl){
                roleEl.addEventListener('change', toggleUserId);
                roleEl.addEventListener('change', toggleProfilSiswa);
                // init berdasarkan old value
                toggleUserId();
                toggleProfilSiswa();
            }
            if(btnGenerate && inputUserId){
                btnGenerate.addEventListener('click', function(){
                    if(nextUserId) inputUserId.value = nextUserId;
                    // pastikan field terlihat
                    if(fieldUserId) fieldUserId.style.display = '';
                    inputUserId.focus();
                });
            }
            // Saat submit, jika role admin pastikan user_id tidak terkirim (disabled sudah)
            const form = document.querySelector('form[action*="menu-user"]');
            if(form){
                form.addEventListener('submit', function(){
                    if(roleEl && roleEl.value === 'admin' && inputUserId){
                        inputUserId.disabled = true;
                    }
                });
            }
        })();
    </script>
</body>
</html>
