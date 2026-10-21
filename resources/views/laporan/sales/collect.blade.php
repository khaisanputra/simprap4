<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collect Data Sales — Laporan #{{ $laporan->id }} - Sistem Absensi PKL Sandya Network</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    <style>
        .laporan-info-card { background:#fff; border:1px solid #E5E7EB; border-radius:16px; padding:18px; margin-bottom:20px; box-shadow:0 4px 16px rgba(15,76,129,0.06); }
        .laporan-info-card h3 { font-size:0.95rem; font-weight:700; color:#0F4C81; margin:0 0 12px; display:flex; align-items:center; gap:8px; }
        .identitas-box { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; background:#F8FAFC; border:1px solid #E5E7EB; border-radius:12px; padding:14px 16px; margin-bottom:14px; }
        .ident-item { display:flex; flex-direction:column; gap:3px; }
        .ident-item .label { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.06em; color:#6B7280; font-weight:600; }
        .ident-item .value { font-size:0.9rem; font-weight:600; color:#1F2937; word-break:break-word; }
        .foto-thumb-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); gap:10px; margin-top:10px; }
        .foto-thumb-grid img { width:100%; height:90px; object-fit:cover; border-radius:10px; border:1px solid #E5E7EB; }
        .collect-card { background:#fff; border:1px solid #E5E7EB; border-radius:18px; box-shadow:0 10px 30px rgba(15,76,129,0.08); padding:22px; margin-bottom:22px; }
        .collect-card h3 { font-size:1.05rem; font-weight:700; color:#0F4C81; margin-bottom:4px; display:flex; align-items:center; gap:10px; }
        .collect-subtitle { font-size:0.82rem; color:#6B7280; margin-bottom:16px; }
        .jam-info { display:flex; align-items:center; gap:8px; font-size:0.78rem; padding:10px 12px; border-radius:10px; margin-bottom:14px; }
        .jam-info.open { background:#F0FDF4; color:#166534; border:1px solid #BBF7D0; }
        .jam-info.closed { background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; }
        .customer-item { background:#F8FAFC; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:14px; position:relative; }
        .customer-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
        .customer-title { font-size:0.95rem; font-weight:700; color:#0F4C81; display:flex; align-items:center; gap:8px; }
        .customer-title span.num { background:#0F4C81; color:#fff; width:28px; height:28px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; font-size:0.78rem; }
        .btn-remove { background:#FEF2F2; color:#DC2626; border:1px solid #FECACA; border-radius:9px; padding:6px 10px; font-size:0.78rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
        .btn-remove:hover { background:#DC2626; color:#fff; border-color:#DC2626; }
        .form-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }
        @media(max-width:640px){ .form-grid{grid-template-columns:1fr;} .collect-card{padding:16px;} }
        .form-group { display:flex; flex-direction:column; gap:6px; }
        .form-label { font-size:0.82rem; font-weight:600; color:#374151; }
        .form-label span.req { color:#DC2626; }
        .form-control { width:100%; border:1px solid #E5E7EB; border-radius:10px; padding:10px 12px; font-size:0.88rem; background:#fff; outline:none; transition:border-color .2s, box-shadow .2s; font-family:inherit; }
        .form-control:focus { border-color:#3B82F6; box-shadow:0 0 0 3px rgba(59,130,246,0.15); }
        textarea.form-control { resize:vertical; min-height:70px; line-height:1.6; }
        .collect-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:16px; }
        .btn-add { background:#EFF6FF; color:#0F4C81; border:1px solid #DBEAFE; border-radius:10px; padding:10px 16px; font-size:0.85rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:all .2s; }
        .btn-add:hover:not(:disabled) { background:#0F4C81; color:#fff; border-color:#0F4C81; }
        .btn-add:disabled { opacity:0.5; cursor:not-allowed; }
        .counter-info { font-size:0.78rem; color:#6B7280; font-weight:500; }
        .counter-info strong { color:#0F4C81; }
        .btn-submit { background:#0F4C81; color:#fff; border:1px solid #0F4C81; border-radius:10px; padding:12px 22px; font-size:0.9rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:all .2s; }
        .btn-submit:hover { background:#0d3f6d; }
        .btn-cancel { background:#fff; color:#374151; border:1px solid #E5E7EB; border-radius:10px; padding:12px 18px; font-size:0.88rem; font-weight:500; cursor:pointer; display:inline-flex; align-items:center; gap:8px; text-decoration:none; transition:all .2s; }
        .btn-cancel:hover { border-color:#9CA3AF; background:#F9FAFB; }
        @media(max-width:640px){ .collect-actions{flex-direction:column; align-items:stretch;} .btn-submit, .btn-cancel, .btn-add{justify-content:center; width:100%;} }
        .teks-preview { background:#F8FAFC; border:1px solid #E5E7EB; border-radius:10px; padding:12px 14px; font-size:0.88rem; color:#374151; line-height:1.7; white-space:pre-wrap; word-break:break-word; }
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
                    <h1 class="page-title-main">Collect Data Sales</h1>
                    <p class="page-title-sub">Lengkapi data customer untuk laporan utama <strong>#{{ $laporan->id }}</strong> — tanggal {{ $laporan->tanggal_laporan ? \Carbon\Carbon::parse($laporan->tanggal_laporan)->translatedFormat('d F Y') : '-' }}</p>
                </div>

                <div style="display:flex; gap:8px; margin-bottom:18px;">
                    <a href="{{ route('laporan.sales.data') }}" class="btn-cancel" style="padding:8px 14px; font-size:0.82rem;"><i class="fa-solid fa-arrow-left"></i> Kembali ke Data Sales</a>
                    <a href="{{ route('laporan.sales.index') }}" class="btn-cancel" style="padding:8px 14px; font-size:0.82rem; background:#F8FAFC;"><i class="fa-solid fa-handshake"></i> Form Sales</a>
                </div>

                @if(session('sales_success'))
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('sales_success') }}</div>
                @endif
                @if(session('sales_error'))
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('sales_error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin:0; padding-left:18px;">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <!-- INFO LAPORAN UTAMA -->
                <div class="laporan-info-card">
                    <h3><i class="fa-solid fa-file-lines"></i> Laporan Utama #{{ $laporan->id }}</h3>
                    <div class="identitas-box">
                        <div class="ident-item"><span class="label">Nama Siswa</span><span class="value">{{ $laporan->user?->nama ?? $laporan->user?->username ?? '-' }}</span></div>
                        <div class="ident-item"><span class="label">Username</span><span class="value">{{ $laporan->user?->username ?? '-' }}</span></div>
                        <div class="ident-item"><span class="label">Tanggal Laporan</span><span class="value">{{ $laporan->tanggal_laporan ? \Carbon\Carbon::parse($laporan->tanggal_laporan)->translatedFormat('d F Y') : '-' }}</span></div>
                    </div>
                    <div style="margin-bottom:10px;">
                        <div style="font-size:0.78rem; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Teks Laporan</div>
                        <div class="teks-preview">{{ $laporan->teks_laporan }}</div>
                    </div>
                    @if($laporan->fotos && $laporan->fotos->isNotEmpty())
                        <div>
                            <div style="font-size:0.78rem; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Foto Dokumentasi ({{ $laporan->fotos->count() }} foto)</div>
                            <div class="foto-thumb-grid">
                                @foreach($laporan->fotos as $f)
                                    <img src="{{ route('laporan.foto.show', [$laporan->id, $f->id]) }}" alt="Foto {{ $loop->iteration }}" loading="lazy">
                                @endforeach
                            </div>
                        </div>
                    @elseif(!empty($laporan->foto))
                        <div>
                            <div style="font-size:0.78rem; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Foto Dokumentasi</div>
                            <div class="foto-thumb-grid">
                                <img src="{{ route('laporan.foto', $laporan->id) }}" alt="Foto Laporan" loading="lazy">
                            </div>
                        </div>
                    @endif
                    @if($laporan->sales && $laporan->sales->count() > 0)
                        <div style="margin-top:12px; background:#EFF6FF; border:1px solid #DBEAFE; border-radius:10px; padding:10px 12px; font-size:0.82rem; color:#1E40AF;">
                            <i class="fa-solid fa-circle-info"></i> Laporan ini sudah memiliki <strong>{{ $laporan->sales->count() }} data customer</strong> sebelumnya. Anda dapat menambah lagi (total akan terakumulasi).
                        </div>
                    @endif
                </div>

                <!-- JAM INFO -->
                @if(isset($jamDibuka))
                    @if($jamDibuka)
                        <div class="jam-info open"><i class="fa-solid fa-circle-check"></i> Jam laporan dibuka: {{ $jamLaporan['mulai'] }} - {{ $jamLaporan['selesai'] }} WIB. Anda dapat menyimpan data sekarang.</div>
                    @else
                        <div class="jam-info closed"><i class="fa-solid fa-clock"></i> Collect Data Sales hanya dapat dilakukan pada jam {{ $jamLaporan['mulai'] }} - {{ $jamLaporan['selesai'] }} WIB.</div>
                    @endif
                @endif

                <!-- FORM COLLECT -->
                <div class="collect-card">
                    <h3><i class="fa-solid fa-handshake"></i> Data Customer</h3>
                    <p class="collect-subtitle">Isi minimal 1 dan maksimal 10 data customer dalam satu kali submit. Semua data akan tersimpan dengan <code>laporan_id = {{ $laporan->id }}</code>.</p>

                    <form method="POST" action="{{ route('laporan.sales.collect.store', $laporan->id) }}" id="collectForm">
                        @csrf

                        <div id="customersContainer">
                            <!-- Row awal 1 -->
                        </div>

                        <div class="collect-actions">
                            <button type="button" id="btnAdd" class="btn-add" onclick="addCustomer()"><i class="fa-solid fa-plus"></i> Tambah Data Customer</button>
                            <span class="counter-info"><strong id="countInfo">1</strong>/10 data</span>
                            <span id="limitInfo" style="font-size:0.78rem; color:#DC2626; display:none;"><i class="fa-solid fa-triangle-exclamation"></i> Maksimal 10 data tercapai</span>
                        </div>

                        <div style="height:1px; background:#E5E7EB; margin:20px 0;"></div>

                        <div class="collect-actions" style="justify-content:flex-start;">
                            <button type="submit" class="btn-submit"><i class="fa-solid fa-floppy-disk"></i> Simpan Data Customer</button>
                            <a href="{{ route('laporan.sales.data') }}" class="btn-cancel"><i class="fa-solid fa-xmark"></i> Batal</a>
                        </div>
                    </form>
                </div>

                <!-- TEMPLATE ROW (hidden) -->
                <template id="customerTemplate">
                    <div class="customer-item" data-index="__INDEX__">
                        <div class="customer-header">
                            <div class="customer-title"><span class="num">__NUM__</span> Data Customer #__NUM__</div>
                            <button type="button" class="btn-remove" onclick="removeCustomer(this)"><i class="fa-solid fa-trash"></i> Hapus</button>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nama Customer <span class="req">*</span></label>
                                <input type="text" name="customers[__INDEX__][nama_customer]" class="form-control" placeholder="Nama lengkap customer" required maxlength="255">
                            </div>
                            <div class="form-group">
                                <label class="form-label">No Telp</label>
                                <input type="text" name="customers[__INDEX__][no_telp]" class="form-control" placeholder="08xxxxxxxxxx" maxlength="30">
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label class="form-label">Alamat Customer <span class="req">*</span></label>
                                <textarea name="customers[__INDEX__][alamat_customer]" class="form-control" rows="2" placeholder="Alamat lengkap customer" required maxlength="1000"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Provider yg Digunakan <span class="req">*</span></label>
                                <select name="customers[__INDEX__][provider]" class="form-control" required>
                                    <option value="" disabled selected>Pilih Provider</option>
                                    @foreach($providers as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kelebihan</label>
                                <input type="text" name="customers[__INDEX__][kelebihan]" class="form-control" placeholder="Kelebihan layanan" maxlength="5000">
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label class="form-label">Kekurangan</label>
                                <textarea name="customers[__INDEX__][kekurangan]" class="form-control" rows="2" placeholder="Kekurangan layanan" maxlength="5000"></textarea>
                            </div>
                        </div>
                    </div>
                </template>

                <footer class="footer"><span>© 2026 PT Sandya Network</span><span class="footer-sep">•</span><span>Sistem Absensi PKL</span></footer>
            </main>
        </div>
    </div>

    <script src="{{ asset('js/dashboard.js') }}"></script>
    @if(auth()->check() && auth()->user()->role === 'admin')
    <script src="{{ asset('js/admin-notifikasi.js') }}"></script>
    @endif
    <script>
        let customerCount = 0;
        const maxCustomers = 10;
        const container = document.getElementById('customersContainer');
        const template = document.getElementById('customerTemplate');
        const btnAdd = document.getElementById('btnAdd');
        const countInfo = document.getElementById('countInfo');
        const limitInfo = document.getElementById('limitInfo');

        function updateCounter() {
            countInfo.textContent = customerCount;
            if (customerCount >= maxCustomers) {
                btnAdd.disabled = true;
                btnAdd.style.display = 'none';
                limitInfo.style.display = 'inline';
            } else {
                btnAdd.disabled = false;
                btnAdd.style.display = 'inline-flex';
                limitInfo.style.display = 'none';
            }
            // Update remove buttons visibility: hide if only 1
            const items = container.querySelectorAll('.customer-item');
            items.forEach(item => {
                const rm = item.querySelector('.btn-remove');
                if (rm) rm.style.display = items.length <= 1 ? 'none' : 'inline-flex';
            });
        }

        function reindexCustomers() {
            const items = container.querySelectorAll('.customer-item');
            items.forEach((item, idx) => {
                const num = idx + 1;
                item.dataset.index = idx;
                const title = item.querySelector('.customer-title');
                if (title) {
                    title.innerHTML = '<span class="num">'+num+'</span> Data Customer #'+num;
                }
                // Update name attributes
                item.querySelectorAll('[name]').forEach(el => {
                    const name = el.getAttribute('name');
                    const newName = name.replace(/customers\[\d+\]/, 'customers['+idx+']');
                    el.setAttribute('name', newName);
                });
            });
            customerCount = items.length;
            updateCounter();
        }

        function addCustomer() {
            if (customerCount >= maxCustomers) return;
            const html = template.innerHTML.replace(/__INDEX__/g, customerCount).replace(/__NUM__/g, customerCount + 1);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const node = wrapper.firstElementChild;
            container.appendChild(node);
            customerCount++;
            updateCounter();
            // Reindex to ensure title numbers correct after add
            reindexCustomers();
            // Scroll to new item
            node.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function removeCustomer(btn) {
            const item = btn.closest('.customer-item');
            if (!item) return;
            const items = container.querySelectorAll('.customer-item');
            if (items.length <= 1) {
                alert('Minimal 1 data customer harus diisi.');
                return;
            }
            item.remove();
            reindexCustomers();
        }

        // Init with 1 row
        document.addEventListener('DOMContentLoaded', function() {
            addCustomer();
        });

        // Frontend validation before submit
        document.getElementById('collectForm')?.addEventListener('submit', function(e) {
            const items = container.querySelectorAll('.customer-item');
            if (items.length < 1 || items.length > 10) {
                e.preventDefault();
                alert('Jumlah data customer harus antara 1 sampai 10.');
                return;
            }
        });
    </script>
</body>
</html>
