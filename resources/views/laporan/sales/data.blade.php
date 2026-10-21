<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Sales - Sistem Absensi PKL Sandya Network</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    <style>
        .laporan-form-card { background:#fff; border:1px solid #E5E7EB; border-radius:18px; box-shadow:0 10px 30px rgba(15,76,129,0.08); padding:22px; margin-bottom:22px; }
        .empty-state { text-align:center; padding:36px 20px; background:#fff; border:1px solid #E5E7EB; border-radius:16px; color:#6B7280; }
        .empty-state i { font-size:2rem; color:#CBD5E1; margin-bottom:10px; display:block; }
        .btn-tool { padding:7px 12px; border-radius:9px; font-size:0.78rem; font-weight:500; border:1px solid #E5E7EB; background:#fff; color:#374151; display:inline-flex; align-items:center; gap:6px; text-decoration:none; cursor:pointer; transition:all .2s; }
        .btn-tool:hover { border-color:#3B82F6; color:#3B82F6; background:#EFF6FF; }
        .btn-tool.excel { border-color:#16A34A; color:#16A34A; }
        .btn-tool.excel:hover { background:#F0FDF4; }
        .btn-tool.primary { background:#0F4C81; color:#fff; border-color:#0F4C81; }
        .btn-tool.primary:hover { background:#0d3f6d; color:#fff; }
        .laporan-tabs { display:flex; gap:8px; margin-bottom:18px; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:6px; width:fit-content; }
        .laporan-tab { padding:9px 18px; border-radius:10px; font-size:0.85rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:all .2s; border:1px solid transparent; color:#6B7280; background:transparent; }
        .laporan-tab.active { background:#0F4C81; color:#fff; border-color:#0F4C81; box-shadow:0 4px 12px rgba(15,76,129,0.2); }
        .laporan-tab:not(.active):hover { background:#F8FAFC; color:#0F4C81; border-color:#E5E7EB; }
        @media(max-width:640px){ .laporan-tabs{width:100%;} .laporan-tab{flex:1; justify-content:center;} }

        /* Filter compact */
        .filter-card { background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:14px; margin-bottom:16px; box-shadow:0 2px 10px rgba(15,76,129,0.04); }
        .filter-row { display:flex; gap:10px; align-items:end; flex-wrap:wrap; }
        .filter-row .fg-search { flex:1.2; min-width:220px; }
        .filter-row .fg-date { min-width:165px; flex:0.8; }
        .filter-row .fg-collector { min-width:200px; flex:1; }
        .filter-row .fg-provider { min-width:200px; flex:1; }
        .filter-row .fg-actions { display:flex; gap:8px; align-self:end; flex-wrap:wrap; }
        .filter-row .form-group { display:flex; flex-direction:column; gap:5px; flex:1; }
        .filter-row .form-label { font-size:0.70rem; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:0.04em; }
        .filter-row .form-control { height:38px; font-size:0.85rem; width:100%; border:1px solid #E5E7EB; border-radius:10px; padding:10px 12px; background:#F8FAFC; outline:none; transition:border-color .2s, box-shadow .2s; font-family:inherit; }
        .filter-row .form-control:focus { border-color:#3B82F6; box-shadow:0 0 0 3px rgba(59,130,246,0.15); background:#fff; }
        @media(max-width:768px){
            .filter-row .fg-search { flex:1 1 100%; min-width:100%; }
            .filter-row .fg-date, .filter-row .fg-collector, .filter-row .fg-provider { flex:1 1 calc(50% - 5px); min-width:calc(50% - 5px); }
            .filter-row .fg-actions { flex:1 1 100%; justify-content:flex-start; }
            .filter-row .fg-actions .btn-tool { flex:1; justify-content:center; }
        }
        @media(max-width:480px){
            .filter-row .fg-date, .filter-row .fg-collector, .filter-row .fg-provider { flex:1 1 100%; min-width:100%; }
        }

        /* Sales table compact */
        .sales-table-wrap { background:#fff; border:1px solid #E5E7EB; border-radius:16px; overflow:hidden; box-shadow:0 4px 16px rgba(15,76,129,0.06); }
        .sales-table { width:100%; border-collapse:collapse; min-width:720px; }
        .sales-table thead th { background:#F8FAFC; color:#6B7280; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; padding:12px 14px; text-align:left; border-bottom:1px solid #E5E7EB; white-space:nowrap; }
        .sales-table thead th:first-child { text-align:center; }
        .sales-table tbody td { padding:13px 14px; font-size:0.84rem; color:#1F2937; border-bottom:1px solid #F3F4F6; vertical-align:middle; }
        .sales-table tbody tr:last-child td { border-bottom:none; }
        .sales-table tbody tr:hover { background:#F8FAFC; }
        .badge-provider { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:0.73rem; font-weight:600; background:#EFF6FF; color:#1E40AF; border:1px solid #DBEAFE; white-space:nowrap; letter-spacing:0.02em; }
        .telp-link { color:#0F4C81; text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:5px; }
        .telp-link:hover { text-decoration:underline; }
        .telp-empty { color:#9CA3AF; font-style:italic; font-size:0.82rem; }
        .btn-icon { width:34px; height:34px; border-radius:9px; border:1px solid #E5E7EB; background:#fff; color:#374151; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all .2s; text-decoration:none; font-size:0.82rem; }
        .btn-icon:hover { border-color:#0F4C81; color:#0F4C81; background:#EFF6FF; }
        .btn-icon.detail { color:#0F4C81; border-color:#DBEAFE; background:#EFF6FF; }
        .btn-icon.detail:hover { background:#0F4C81; color:#fff; border-color:#0F4C81; }
        .btn-icon.delete { color:#DC2626; border-color:#FECACA; background:#FEF2F2; }
        .btn-icon.delete:hover { background:#DC2626; color:#fff; border-color:#DC2626; }
        .action-group { display:flex; gap:6px; justify-content:center; }

        /* Mobile cards */
        .sales-cards { display:none; gap:12px; flex-direction:column; margin-bottom:16px; }
        .sales-card { background:#fff; border:1px solid #E5E7EB; border-radius:16px; padding:16px; box-shadow:0 2px 10px rgba(15,76,129,0.06); transition:box-shadow .2s; }
        .sales-card:hover { box-shadow:0 6px 20px rgba(15,76,129,0.10); }
        .sales-card-head { display:flex; justify-content:space-between; align-items:start; gap:10px; margin-bottom:10px; }
        .sales-card-name { font-size:0.95rem; font-weight:700; color:#1F2937; line-height:1.3; }
        .sales-card-no { font-size:0.70rem; font-weight:600; color:#6B7280; background:#F8FAFC; border:1px solid #E5E7EB; padding:3px 7px; border-radius:999px; white-space:nowrap; }
        .sales-card-meta { display:flex; flex-direction:column; gap:6px; margin-bottom:12px; }
        .sales-card-meta-item { display:flex; align-items:center; gap:8px; font-size:0.82rem; color:#4B5563; }
        .sales-card-meta-item i { width:16px; text-align:center; color:#6B7280; font-size:0.78rem; }
        .sales-card-foot { display:flex; gap:8px; }
        .sales-card-foot .btn-tool { flex:1; justify-content:center; height:38px; }
        @media(max-width:768px){
            .sales-table-wrap { display:none; }
            .sales-cards { display:flex; }
            .card-table-header { padding:14px 16px; }
        }
        @media(min-width:769px){
            .sales-cards { display:none !important; }
        }

        /* Modal */
        .modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,0.55); backdrop-filter:blur(4px); display:none; align-items:center; justify-content:center; z-index:9999; padding:16px; }
        .modal-overlay.show { display:flex; }
        .modal-box { background:#fff; border-radius:18px; width:100%; max-width:640px; max-height:90vh; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.2); display:flex; flex-direction:column; animation:modalIn .22s ease; }
        @keyframes modalIn { from { opacity:0; transform:translateY(10px) scale(0.98); } to { opacity:1; transform:translateY(0) scale(1); } }
        .modal-head { display:flex; align-items:center; justify-content:space-between; padding:18px 20px; border-bottom:1px solid #E5E7EB; background:linear-gradient(135deg,#F8FAFC 0%, #EFF6FF 100%); }
        .modal-head h3 { font-size:1.02rem; font-weight:700; color:#0F4C81; margin:0; display:flex; align-items:center; gap:10px; }
        .modal-close { width:36px; height:36px; border-radius:10px; border:1px solid #E5E7EB; background:#fff; color:#6B7280; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all .2s; }
        .modal-close:hover { background:#FEF2F2; color:#DC2626; border-color:#FECACA; }
        .modal-body { padding:20px; overflow-y:auto; flex:1; }
        .detail-section { margin-bottom:18px; }
        .detail-section:last-child { margin-bottom:0; }
        .detail-section-title { font-size:0.75rem; font-weight:700; color:#0F4C81; text-transform:uppercase; letter-spacing:0.05em; border-left:3px solid #0F4C81; padding-left:10px; margin:0 0 10px; display:flex; align-items:center; gap:6px; }
        .detail-grid { display:grid; grid-template-columns:140px 1fr; gap:8px 14px; background:#F8FAFC; border:1px solid #E5E7EB; border-radius:12px; padding:14px 16px; }
        .detail-label { font-size:0.82rem; color:#6B7280; font-weight:500; }
        .detail-value { font-size:0.88rem; color:#1F2937; font-weight:600; word-break:break-word; white-space:pre-wrap; }
        .detail-value.long { font-weight:400; line-height:1.65; white-space:pre-wrap; word-wrap:break-word; }
        .detail-provider-badge { display:inline-flex; padding:6px 12px; border-radius:999px; background:#EFF6FF; color:#1E40AF; border:1px solid #DBEAFE; font-size:0.85rem; font-weight:700; }
        @media(max-width:640px){ .detail-grid{grid-template-columns:1fr; gap:4px;} .modal-box{max-height:92vh;} .modal-body{padding:16px;} }
        .modal-foot { padding:14px 20px; border-top:1px solid #E5E7EB; background:#FBFDFF; display:flex; justify-content:flex-end; }
        .sub-tabs { display:flex; gap:8px; margin-bottom:16px; }
        .sub-tab { padding:8px 14px; border-radius:10px; font-size:0.82rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; border:1px solid #E5E7EB; background:#fff; color:#374151; transition:all .2s; }
        .sub-tab.active { background:#0F4C81; color:#fff; border-color:#0F4C81; }
        .sub-tab:hover:not(.active) { border-color:#0F4C81; color:#0F4C81; background:#EFF6FF; }
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
                    <h1 class="page-title-main">Data Sales</h1>
                    <p class="page-title-sub">Filter, cari, lihat detail, hapus, dan export data customer Sales.</p>
                </div>

                <!-- Tabs -->
                <div class="laporan-tabs">
                    <a href="{{ route('laporan.index') }}" class="laporan-tab"><i class="fa-solid fa-screwdriver-wrench"></i> Teknisi</a>
                    <a href="{{ route('laporan.sales.index') }}" class="laporan-tab"><i class="fa-solid fa-handshake"></i> Sales</a>
                </div>

                <div class="sub-tabs">
                    <a href="{{ route('laporan.sales.index') }}" class="sub-tab"><i class="fa-solid fa-pen-to-square"></i> Form Sales</a>
                    <a href="{{ route('laporan.sales.data') }}" class="sub-tab active"><i class="fa-solid fa-table"></i> Data Sales</a>
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

                <!-- FILTER COMPACT -->
                <div class="filter-card">
                    <form method="GET" action="{{ route('laporan.sales.data') }}">
                        <div class="filter-row">
                            <div class="form-group fg-search">
                                <label class="form-label" for="filter_cari">Cari Customer</label>
                                <input type="text" id="filter_cari" name="cari" value="{{ request('cari') }}" placeholder="🔎 Cari Customer, Alamat, No Telp..." class="form-control">
                            </div>
                            <div class="form-group fg-date">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control">
                            </div>
                            @if($isAdmin)
                            <div class="form-group fg-collector">
                                <label class="form-label">Collector</label>
                                <select name="collector_id" class="form-control">
                                    <option value="">Semua Collector</option>
                                    @foreach($collectors as $c)
                                        <option value="{{ $c->id }}" {{ (string)request('collector_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->nama }} ({{ $c->username }})</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="form-group fg-provider">
                                <label class="form-label">Provider</label>
                                <select name="provider" class="form-control">
                                    <option value="">Semua Provider</option>
                                    @foreach($providers as $p)
                                        <option value="{{ $p }}" {{ request('provider') === $p ? 'selected' : '' }}>{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="filter-row" style="margin-top:10px;">
                            <div class="form-group fg-date">
                                <label class="form-label">Dari Tanggal</label>
                                <input type="date" name="tanggal_dari" value="{{ request('tanggal_dari') }}" class="form-control">
                            </div>
                            <div class="form-group fg-date">
                                <label class="form-label">Sampai Tanggal</label>
                                <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}" class="form-control">
                            </div>
                            <div class="fg-actions" style="margin-left:auto;">
                                <button type="submit" class="btn-tool primary" style="height:38px;"><i class="fa-solid fa-magnifying-glass"></i> Terapkan</button>
                                <a href="{{ route('laporan.sales.data') }}" class="btn-tool" style="height:38px;"><i class="fa-solid fa-rotate"></i> Reset</a>
                                @php $exportQuery = request()->query(); @endphp
                                <a href="{{ route('laporan.sales.export', $exportQuery) }}" class="btn-tool excel" style="height:38px;"><i class="fa-solid fa-file-excel"></i> Export Excel</a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- DATA SALES : TABLE + CARDS -->
                @if($laporans->count() > 0)
                <div class="sales-table-wrap">
                    <div class="card-table-header" style="display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-bottom:1px solid #E5E7EB; background:#fff;">
                        <div class="ct-title" style="display:flex; align-items:center; gap:10px;">
                            <i class="fa-solid fa-table" style="color:#0F4C81;"></i>
                            <div>
                                <h3 style="margin:0; font-size:0.95rem; font-weight:700; color:#1F2937;">Data Laporan Sales</h3>
                                <p style="margin:0; font-size:0.75rem; color:#6B7280;">Menampilkan {{ $laporans->total() }} data — klik 👁 untuk detail lengkap</p>
                            </div>
                        </div>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="sales-table">
                            <thead>
                                <tr>
                                    <th style="width:56px; text-align:center;">No</th>
                                    <th style="width:115px;">Tanggal</th>
                                    <th style="min-width:160px;">Collector</th>
                                    <th style="min-width:160px;">Nama Customer</th>
                                    <th style="min-width:140px;">Provider</th>
                                    <th style="min-width:130px;">No. Telp</th>
                                    <th style="width:110px; text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($laporans as $row)
                                    @php
                                        $nomor = ($laporans->currentPage() - 1) * $laporans->perPage() + $loop->iteration;
                                        $tgl = $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') : '-';
                                        $tglLong = $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->translatedFormat('d F Y') : '-';
                                        $collectorName = $row->user?->nama ?? $row->user?->username ?? '-';
                                        $collectorUsername = $row->user?->username ?? '';
                                        $telp = $row->no_telp;
                                        $hasTelp = !empty($telp);
                                    @endphp
                                    <tr>
                                        <td style="text-align:center; font-weight:600; color:#6B7280;">{{ $nomor }}</td>
                                        <td style="white-space:nowrap; font-weight:500;">{{ $tgl }}</td>
                                        <td>
                                            <div style="font-weight:600; color:#1F2937; font-size:0.85rem; line-height:1.3;">{{ $collectorName }}</div>
                                            @if($collectorUsername)<div style="font-size:0.70rem; color:#6B7280;">{{ $collectorUsername }}</div>@endif
                                        </td>
                                        <td style="font-weight:500; color:#111827;">{{ $row->nama_customer }}</td>
                                        <td><span class="badge-provider">{{ $row->provider }}</span></td>
                                        <td>
                                            @if($hasTelp)
                                                <a href="tel:{{ preg_replace('/\s+/', '', $telp) }}" class="telp-link" title="Hubungi {{ $telp }}"><i class="fa-solid fa-phone" style="font-size:0.72rem;"></i> {{ $telp }}</a>
                                            @else
                                                <span class="telp-empty">-</span>
                                            @endif
                                        </td>
                                        <td style="text-align:center;">
                                            <div class="action-group">
                                                <button type="button" class="btn-icon detail" title="Detail" aria-label="Detail"
                                                    data-tanggal="{{ $tglLong }}"
                                                    data-collector="{{ $collectorName }}{{ $collectorUsername ? ' ('.$collectorUsername.')' : '' }}"
                                                    data-nama="{{ $row->nama_customer }}"
                                                    data-telp="{{ $telp ?? '-' }}"
                                                    data-alamat="{{ $row->alamat_customer }}"
                                                    data-provider="{{ $row->provider }}"
                                                    data-kelebihan="{{ $row->kelebihan ?? '-' }}"
                                                    data-kekurangan="{{ $row->kekurangan ?? '-' }}"
                                                    onclick="openSalesDetail(this)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <form method="POST" action="{{ route('laporan.sales.destroy', $row->id) }}" onsubmit="return confirm('Hapus laporan Sales milik {{ $row->nama_customer }}? Tindakan tidak dapat dibatalkan.')" style="display:inline;">@csrf @method('DELETE')<button type="submit" class="btn-icon delete" title="Hapus" aria-label="Hapus"><i class="fa-solid fa-trash"></i></button></form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer" style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-top:1px solid #E5E7EB; background:#F8FAFC; flex-wrap:wrap; gap:10px;">
                        <span style="font-size:0.82rem; color:#6B7280;">Menampilkan <strong style="color:#1F2937;">{{ $laporans->total() }}</strong> laporan Sales</span>
                        <div class="pagination-links">{{ $laporans->links('vendor.pagination.custom') }}</div>
                    </div>
                </div>

                <!-- Mobile Cards -->
                <div class="sales-cards">
                    @foreach($laporans as $row)
                        @php
                            $nomor = ($laporans->currentPage() - 1) * $laporans->perPage() + $loop->iteration;
                            $tglLong = $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->translatedFormat('d F Y') : '-';
                            $collectorName = $row->user?->nama ?? $row->user?->username ?? '-';
                            $telp = $row->no_telp;
                        @endphp
                        <div class="sales-card">
                            <div class="sales-card-head">
                                <div class="sales-card-name">{{ $row->nama_customer }}</div>
                                <span class="sales-card-no">#{{ $nomor }}</span>
                            </div>
                            <div class="sales-card-meta">
                                <div class="sales-card-meta-item"><i class="fa-solid fa-calendar-day"></i> <span>{{ $tglLong }}</span></div>
                                <div class="sales-card-meta-item"><i class="fa-solid fa-user"></i> <span>{{ $collectorName }}</span></div>
                                <div class="sales-card-meta-item"><i class="fa-solid fa-wifi"></i> <span class="badge-provider" style="font-size:0.72rem; padding:3px 8px;">{{ $row->provider }}</span></div>
                                <div class="sales-card-meta-item">
                                    <i class="fa-solid fa-phone"></i>
                                    @if(!empty($telp))
                                        <a href="tel:{{ preg_replace('/\s+/', '', $telp) }}" class="telp-link">{{ $telp }}</a>
                                    @else
                                        <span class="telp-empty">Tidak tersedia</span>
                                    @endif
                                </div>
                            </div>
                            <div class="sales-card-foot">
                                <button type="button" class="btn-tool primary" style="height:38px;"
                                    data-tanggal="{{ $tglLong }}"
                                    data-collector="{{ $collectorName }}{{ $row->user?->username ? ' ('.$row->user->username.')' : '' }}"
                                    data-nama="{{ $row->nama_customer }}"
                                    data-telp="{{ $telp ?? '-' }}"
                                    data-alamat="{{ $row->alamat_customer }}"
                                    data-provider="{{ $row->provider }}"
                                    data-kelebihan="{{ $row->kelebihan ?? '-' }}"
                                    data-kekurangan="{{ $row->kekurangan ?? '-' }}"
                                    onclick="openSalesDetail(this)">
                                    <i class="fa-solid fa-eye"></i> Lihat Detail
                                </button>
                                <form method="POST" action="{{ route('laporan.sales.destroy', $row->id) }}" onsubmit="return confirm('Hapus laporan Sales ini?')" style="display:inline; flex:0 0 auto;">@csrf @method('DELETE')<button type="submit" class="btn-icon delete" style="width:38px; height:38px;" title="Hapus"><i class="fa-solid fa-trash"></i></button></form>
                            </div>
                        </div>
                    @endforeach
                    <div style="display:flex; justify-content:center; padding:8px 0 4px;">{{ $laporans->links('vendor.pagination.custom') }}</div>
                </div>
                @else
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <p style="font-weight:600; color:#374151;">Belum ada laporan Sales</p>
                        @if(!($isAdmin ?? false))
                        <p style="font-size:0.85rem; margin-top:4px;">Buat laporan via <a href="{{ route('laporan.sales.index') }}" style="color:#0F4C81; font-weight:600;">Form Sales</a>.</p>
                        @else
                        <p style="font-size:0.85rem; margin-top:4px;">Belum ada data Sales sesuai filter.</p>
                        @endif
                    </div>
                @endif

                <footer class="footer"><span>© 2026 PT Sandya Network</span><span class="footer-sep">•</span><span>Sistem Absensi PKL</span></footer>
            </main>
        </div>
    </div>

    <!-- Modal Detail Sales -->
    <div id="salesDetailOverlay" class="modal-overlay" aria-hidden="true">
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="salesDetailTitle">
            <div class="modal-head">
                <h3 id="salesDetailTitle"><i class="fa-solid fa-file-lines" style="color:#0F4C81;"></i> Detail Laporan Sales</h3>
                <button type="button" class="modal-close" onclick="closeSalesDetail()" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="detail-section">
                    <div class="detail-section-title"><i class="fa-solid fa-calendar-day"></i> Informasi Laporan</div>
                    <div class="detail-grid">
                        <div class="detail-label">Tanggal</div><div class="detail-value" id="dTanggal">-</div>
                        <div class="detail-label">Collector</div><div class="detail-value" id="dCollector">-</div>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="detail-section-title"><i class="fa-solid fa-user"></i> Data Customer</div>
                    <div class="detail-grid">
                        <div class="detail-label">Nama Customer</div><div class="detail-value" id="dNama">-</div>
                        <div class="detail-label">No. Telp</div><div class="detail-value" id="dTelp">-</div>
                        <div class="detail-label">Alamat Customer</div><div class="detail-value long" id="dAlamat">-</div>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="detail-section-title"><i class="fa-solid fa-wifi"></i> Provider</div>
                    <div style="background:#F8FAFC; border:1px solid #E5E7EB; border-radius:12px; padding:14px 16px;">
                        <span class="detail-provider-badge" id="dProvider">-</span>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="detail-section-title"><i class="fa-solid fa-thumbs-up"></i> Kelebihan</div>
                    <div style="background:#F8FAFC; border:1px solid #E5E7EB; border-radius:12px; padding:14px 16px;">
                        <div class="detail-value long" id="dKelebihan" style="font-weight:400;">-</div>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="detail-section-title"><i class="fa-solid fa-thumbs-down"></i> Kekurangan</div>
                    <div style="background:#F8FAFC; border:1px solid #E5E7EB; border-radius:12px; padding:14px 16px;">
                        <div class="detail-value long" id="dKekurangan" style="font-weight:400;">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn-tool" onclick="closeSalesDetail()"><i class="fa-solid fa-xmark"></i> Tutup</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/dashboard.js') }}"></script>
    @if(auth()->check() && auth()->user()->role === 'admin')
    <script src="{{ asset('js/admin-notifikasi.js') }}"></script>
    @endif
    <script>
        // Modal detail sales
        function openSalesDetail(btn){
            var tanggal = btn.getAttribute('data-tanggal') || '-';
            var collector = btn.getAttribute('data-collector') || '-';
            var nama = btn.getAttribute('data-nama') || '-';
            var telp = btn.getAttribute('data-telp') || '-';
            var alamat = btn.getAttribute('data-alamat') || '-';
            var provider = btn.getAttribute('data-provider') || '-';
            var kelebihan = btn.getAttribute('data-kelebihan') || '-';
            var kekurangan = btn.getAttribute('data-kekurangan') || '-';
            document.getElementById('dTanggal').textContent = tanggal;
            document.getElementById('dCollector').textContent = collector;
            document.getElementById('dNama').textContent = nama;
            document.getElementById('dTelp').textContent = telp && telp !== '-' ? telp : 'Tidak tersedia';
            document.getElementById('dAlamat').textContent = alamat && alamat !== '' ? alamat : '-';
            document.getElementById('dProvider').textContent = provider;
            document.getElementById('dKelebihan').textContent = kelebihan && kelebihan !== '-' ? kelebihan : '-';
            document.getElementById('dKekurangan').textContent = kekurangan && kekurangan !== '-' ? kekurangan : '-';
            var overlay = document.getElementById('salesDetailOverlay');
            overlay.classList.add('show');
            overlay.setAttribute('aria-hidden','false');
            document.body.style.overflow = 'hidden';
        }
        function closeSalesDetail(){
            var overlay = document.getElementById('salesDetailOverlay');
            overlay.classList.remove('show');
            overlay.setAttribute('aria-hidden','true');
            document.body.style.overflow = '';
        }
        document.getElementById('salesDetailOverlay')?.addEventListener('click', function(e){
            if(e.target === this) closeSalesDetail();
        });
        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape'){
                var ov = document.getElementById('salesDetailOverlay');
                if(ov && ov.classList.contains('show')) closeSalesDetail();
            }
        });
    </script>
</body>
</html>
