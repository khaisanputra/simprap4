@php
    $cari = request('cari');
    $roleFilter = request('role');
    $sort = request('sort', 'created_at');
    $order = request('order', 'desc');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu User - Sistem Absensi PKL Sandya Network</title>
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
                <div class="page-title">
                    <h1 class="page-title-main">Menu User</h1>
                    <p class="page-title-sub">
                        Kelola seluruh akun user — tambah, ubah, ganti password, dan hapus user.
                    </p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success" id="flashSuccess">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger" id="flashError">{{ session('error') }}</div>
                @endif

                <!-- ===== USER TABLE CARD ===== -->
                <div class="card-table">
                    <div class="card-table-header">
                        <div class="ct-title">
                            <i class="fa-solid fa-users"></i>
                            <div>
                                <h3>Daftar User</h3>
                                <p>Total <strong>{{ $totalUsers }}</strong> user terdaftar di sistem</p>
                            </div>
                        </div>

                        <div class="ct-actions">
                            <form method="GET" action="{{ route('users.index') }}" class="dashboard-filter-form">
                                <div class="search-wrap">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" name="cari" value="{{ $cari }}" class="search-input" placeholder="Cari nama / username / email / User ID...">
                                    @if ($cari)
                                    <a href="{{ route('users.index') }}" class="search-clear" aria-label="Bersihkan pencarian">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                    </a>
                                    @endif
                                </div>

                                <select name="role" class="filter-select" onchange="this.form.submit()">
                                    <option value="">Semua Role</option>
                                    <option value="admin" {{ $roleFilter === 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="siswa" {{ $roleFilter === 'siswa' ? 'selected' : '' }}>Siswa</option>
                                </select>

                                <button type="submit" class="btn-tool btn-search">
                                    <i class="fa-solid fa-magnifying-glass"></i> Cari
                                </button>
                            </form>

                            <div class="ct-toolbar">
                                <a href="{{ route('users.create') }}" class="btn-tool btn-add-user">
                                    <i class="fa-solid fa-user-plus"></i> Tambah User
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>
<a class="sort-link" href="@sortlink('user_id', $sort, $order)">User ID <i class="fa-solid fa-sort"></i></a>
                                    </th>
<th>
<a class="sort-link" href="@sortlink('nama', $sort, $order)">Nama <i class="fa-solid fa-sort"></i></a>
                                    </th>
                                    <th>
<a class="sort-link" href="@sortlink('username', $sort, $order)">Username <i class="fa-solid fa-sort"></i></a>
                                    </th>
                                    <th>
<a class="sort-link" href="@sortlink('email', $sort, $order)">Email <i class="fa-solid fa-sort"></i></a>
                                    </th>
                                    <th>
<a class="sort-link" href="@sortlink('role', $sort, $order)">Role <i class="fa-solid fa-sort"></i></a>
                                    </th>
                                    <th>
<a class="sort-link" href="@sortlink('status', $sort, $order)">Status <i class="fa-solid fa-sort"></i></a>
                                    </th>
                                    <th>
<a class="sort-link" href="@sortlink('created_at', $sort, $order)">Tanggal Dibuat <i class="fa-solid fa-sort"></i></a>
                                    </th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                @forelse ($users as $user)
                                <tr>
                                    <td>{{ $users->firstItem() + $loop->index }}</td>
                                    <td>
                                        @if($user->user_id)
                                            <span style="font-family:monospace; font-weight:600; white-space:nowrap;">{{ $user->user_id }}</span>
                                        @else
                                            <span style="color:#9CA3AF;">-</span>
                                        @endif
                                    </td>
                                    <td><div class="cell-nama">{{ $user->nama }}</div></td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <span class="badge-status {{ $user->role === 'admin' ? 'badge-admin' : 'badge-siswa' }}">
                                            {{ $user->role === 'admin' ? 'Admin' : 'Siswa' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status {{ $user->status === 'Aktif' ? 'badge-masuk' : 'badge-nonaktif' }}">
                                            {{ $user->status }}
                                        </span>
                                    </td>
                                    <td>{{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->translatedFormat('d F Y') : '-' }}</td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="{{ route('users.show', $user) }}" class="btn-action btn-detail" title="Detail">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="{{ route('users.edit', $user) }}" class="btn-action btn-edit" title="Edit">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <button type="button" class="btn-action btn-pass" title="Ganti Password"
                                                data-user-id="{{ $user->id }}"
                                                data-user-name="{{ $user->nama }}"
                                                onclick="openPasswordModal(this)">
                                                <i class="fa-solid fa-key"></i>
                                            </button>
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="delete-form" data-user-name="{{ $user->nama }}" onsubmit="return confirmDelete(event, this)">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action btn-delete" title="Hapus">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="table-empty">
                                            <i class="fa-solid fa-users-slash"></i>
                                            <p>Tidak ada user yang cocok.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer">
                        <span>Menampilkan <strong>{{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }}</strong> dari {{ $users->total() }} user</span>
                        <div class="pagination-links">{{ $users->links('vendor.pagination.custom') }}</div>
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

    <!-- ===== MODAL GANTI PASSWORD ===== -->
    <div class="modal-overlay" id="passwordModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Ganti Password</h3>
                <button class="modal-close" onclick="closePasswordModal()" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" id="passwordForm">
                @csrf
                @method('PUT')
                <p class="modal-sub">Ganti password untuk: <strong id="passwordUserName"></strong></p>

                <div class="form-group">
                    <label for="password" class="form-label">Password Baru</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 8 karakter" required minlength="8">
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru" required minlength="8">
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closePasswordModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan Password</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('js/dashboard.js') }}"></script>
    @if(auth()->check() && auth()->user()->role === 'admin')
    <script src="{{ asset('js/admin-notifikasi.js') }}"></script>
    @endif
</body>
</html>
