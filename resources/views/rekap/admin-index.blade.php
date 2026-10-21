@php
    $role = $role ?? 'admin';
    $jenis = $jenis ?? 'bulanan';
    $start_date = $start_date ?? ($start->toDateString() ?? now()->toDateString());
    $end_date = $end_date ?? ($end->toDateString() ?? now()->toDateString());
    $tanggal = $tanggal ?? $start_date;
    $tanggalList = $tanggalList ?? collect();
    $matrix = $matrix ?? [];
    $totalsKalender = $totalsKalender ?? [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absen — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    <style>
        .filter-fields-rekap { display:grid; gap:14px; }
        .filter-row { display:grid; gap:12px; align-items:end; }
        .filter-row-2 { grid-template-columns: 1.4fr 0.9fr auto; }
        .filter-row-jenis-bulanan { grid-template-columns: 0.7fr 0.7fr auto; }
        .filter-row-jenis-mingguan { grid-template-columns: 1fr auto; }
        .filter-row-jenis-periode { grid-template-columns: 1fr 1fr auto; }
        @media (max-width: 768px){
            .filter-row, .filter-row-2, .filter-row-jenis-bulanan, .filter-row-jenis-mingguan, .filter-row-jenis-periode { grid-template-columns: 1fr; }
        }
        /* Kalender styles */
        .rekap-calendar-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; border-top:1px solid var(--border); }
        .rekap-calendar-table { width:max-content; min-width:100%; border-collapse:collapse; }
        .rekap-calendar-table th, .rekap-calendar-table td { border-bottom:1px solid #F1F5F9; white-space:nowrap; }
        .rekap-calendar-table thead th { background:var(--primary); color:#fff; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.03em; padding:10px 6px; text-align:center; }
        .rekap-calendar-table thead th.sticky-col { background:var(--primary); z-index:4; }
        .rekap-calendar-table tbody td { padding:7px 6px; font-size:0.82rem; text-align:center; }
        .rekap-calendar-table tbody td.sticky-col { background:#fff; }
        .rekap-calendar-table tbody tr:hover td { background:#F8FAFC; }
        .rekap-calendar-table tbody tr:hover td.sticky-col { background:#F8FAFC; }
        .sticky-col { position:sticky; }
        .sticky-no { left:0; min-width:42px; max-width:42px; z-index:3; border-right:1px solid #E2E8F0; }
        .sticky-siswa { left:42px; min-width:210px; max-width:210px; text-align:left !important; padding-left:12px !important; z-index:3; border-right:1px solid #E2E8F0; }
        .rekap-calendar-table thead th.sticky-no, .rekap-calendar-table thead th.sticky-siswa { z-index:5; }
        .sticky-rekap { position:sticky; right:0; background:#fff; z-index:3; }
        .rekap-calendar-table thead th.sticky-rekap-header { background:#0A3A63; }
        .rekap-badge-cell { display:inline-flex; align-items:center; justify-content:center; min-width:26px; height:26px; padding:0 6px; border-radius:7px; font-size:0.75rem; font-weight:700; line-height:1; cursor:default; }
        .rekap-badge-cell.clickable { cursor:pointer; transition:transform 0.15s, box-shadow 0.15s; }
        .rekap-badge-cell.clickable:hover { transform:scale(1.08); box-shadow:0 2px 8px rgba(0,0,0,0.12); }
        .rekap-legend { display:flex; flex-wrap:wrap; gap:10px; padding:12px 16px; border-top:1px solid var(--border); background:#FBFDFF; font-size:0.74rem; color:var(--text-sec); align-items:center; }
        .rekap-legend-item { display:inline-flex; align-items:center; gap:6px; }
        .rekap-legend-dot { width:18px; height:18px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; font-size:0.66rem; font-weight:700; border:1px solid transparent; }
        /* ensure calendar visible on mobile - override old hiding */
        @media (max-width: 640px){
            .rekap-calendar-wrap { margin:0 -14px; padding:0 14px; }
            .sticky-siswa { min-width:160px; max-width:160px; left:42px; }
            .rekap-calendar-table thead th { font-size:0.68rem; padding:8px 4px; }
            .rekap-calendar-table tbody td { padding:6px 4px; }
            .rekap-mobile-list { display:none !important; }
            .rekap-table-wrap { display:block !important; }
        }
        .rekap-totals-cell { font-weight:700; font-size:0.84rem; }
        .rekap-export-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo"><img src="{{ asset('images/logo-sandya2.png') }}" alt="Sandya"></div>
            <div class="brand-text">
                <span class="brand-name">Sandya</span>
                <span class="brand-sub">Absensi PKL</span>
            </div>
            <button class="sidebar-close" id="sidebarClose" aria-label="Tutup menu"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <nav class="sidebar-nav">
            <p class="nav-section">Menu Utama</p>
            <a href="{{ route('dashboard') }}" class="nav-link" data-tooltip="Dashboard"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="{{ route('absen.masuk') }}" class="nav-link" data-tooltip="Absen Masuk"><i class="fa-solid fa-arrow-right-to-bracket" style="color:#10B981"></i><span>Absen Masuk</span></a>
            <a href="{{ route('absen.pulang') }}" class="nav-link" data-tooltip="Absen Pulang"><i class="fa-solid fa-arrow-right-from-bracket" style="color:#F87171"></i><span>Absen Pulang</span></a>
            <a href="{{ route('laporan.index') }}" class="nav-link" data-tooltip="Laporan"><i class="fa-solid fa-file-lines"></i><span>Laporan</span></a>
            <p class="nav-section">Lainnya</p>
            <a href="{{ route('data-absen-masuk.index') }}" class="nav-link" data-tooltip="Data Absen"><i class="fa-solid fa-clipboard-check"></i><span>Data Absen</span></a>
            <a href="{{ route('rekap.admin') }}" class="nav-link active" data-tooltip="Rekap Absen"><i class="fa-solid fa-chart-column"></i><span>Rekap Absen</span></a>
            <a href="{{ route('users.index') }}" class="nav-link" data-tooltip="Menu User"><i class="fa-solid fa-users"></i><span>Menu User</span></a>
            <a href="{{ route('lokasi-kantor.index') }}" class="nav-link" data-tooltip="Pengaturan Lokasi"><i class="fa-solid fa-location-dot"></i><span>Pengaturan Lokasi</span></a>
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
                <div class="navbar-title">
                    <span class="nb-title-main">Rekap Absen</span>
                    <span class="nb-title-sub">{{ $labelHeader ?? 'Rekap Absen' }} @if($isLive ?? false) <span style="background:#DCFCE7;color:#15803D;padding:2px 8px;border-radius:999px;font-size:0.70rem;font-weight:700;">LIVE</span> @else <span style="background:#DBEAFE;color:#1E40AF;padding:2px 8px;border-radius:999px;font-size:0.70rem;font-weight:700;">ARSIP</span> @endif</span>
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
                    <div class="avatar"><img src="https://i.pravatar.cc/100?img=12" alt="Foto"></div>
                    <div class="profile-meta">
                        <span class="profile-name">{{ $username ?? 'Admin' }}</span>
                        <span class="profile-role">Administrator</span>
                    </div>
                    <button class="icon-btn" id="profileDropdownBtn"><i class="fa-solid fa-chevron-down"></i></button>
                    <div class="dropdown-menu" id="profileDropdown">
                        <a href="{{ route('users.index') }}" class="dropdown-item"><i class="fa-solid fa-users"></i> Menu User</a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item dropdown-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</button></form>
                    </div>
                </div>
            </div>
        </header>

        <main class="page-content">
            <div class="page-title">
                <h1 class="page-title-main">Rekap Absen — Semua Siswa</h1>
                <p class="page-title-sub">
                    @if($jenis === 'mingguan')
                        Rekap Mingguan • <strong>{{ $labelPeriode }}</strong>
                    @elseif($jenis === 'periode')
                        Rekap Periode • <strong>{{ $labelPeriode }}</strong>
                    @else
                        Rekap Absen • <strong>{{ $labelPeriode }}</strong> • <span style="font-size:0.82rem;color:var(--text-sec);">{{ $labelRange }}</span>
                    @endif
                    @if($isLive ?? false) — <span style="color:#15803D;font-weight:600;">LIVE</span> @else — <span style="color:#1E40AF;font-weight:600;">ARSIP</span> @endif
                </p>
            </div>

            <div class="card-table filter-form" style="margin-bottom:18px;">
                @if(isset($errors) && $errors->any())
                    <div class="alert alert-danger" style="margin:12px 22px 0 22px; background:#FEF2F2; border:1px solid #FECACA; color:#991B1B; padding:10px 14px; border-radius:8px;">
                        <ul style="margin:0;padding-left:18px">
                            @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif
                <form method="GET" action="{{ route('rekap.admin') }}" id="rekapFilterForm" style="padding:16px 20px;">
                    <div class="filter-fields" style="grid-template-columns: 1.4fr 0.9fr auto; gap:12px; display:grid;">
                        <div class="filter-field">
                            <label class="form-label" for="cari">Cari Siswa</label>
                            <input type="text" name="cari" id="cari" value="{{ $cari }}" placeholder="Nama, User ID, atau username..." class="form-control">
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="jenis">Jenis Rekap</label>
                            <select name="jenis" id="jenis" class="form-control filter-select">
                                <option value="mingguan" @selected($jenis==='mingguan')>Mingguan</option>
                                <option value="bulanan" @selected($jenis==='bulanan')>Bulanan</option>
                                <option value="periode" @selected($jenis==='periode')>Periode</option>
                            </select>
                        </div>
                        <div class="filter-buttons" style="align-self:end;">
                            <button type="submit" class="btn-tool" style="background:var(--primary); color:#fff; border-color:var(--primary);"><i class="fa-solid fa-magnifying-glass"></i> Tampilkan</button>
                            <a href="{{ route('rekap.admin') }}" class="btn-tool" title="Reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                        </div>
                    </div>

                    {{-- Bulanan fields --}}
                    <div id="fields-bulanan" class="filter-fields" style="margin-top:14px; grid-template-columns: 0.7fr 0.7fr auto; gap:12px; display:{{ $jenis==='bulanan' ? 'grid' : 'none' }};">
                        <div class="filter-field">
                            <label class="form-label" for="bulan">Bulan</label>
                            <select name="bulan" id="bulan" class="form-control filter-select">
                                @for($m=1;$m<=12;$m++)
                                    <option value="{{ $m }}" @selected(($bulan ?? now()->format('n'))==$m)>{{ \App\Models\MonthlyStatistic::namaBulan($m) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="tahun">Tahun</label>
                            <select name="tahun" id="tahun" class="form-control filter-select">
                                @foreach($tahunList as $th)
                                    <option value="{{ $th }}" @selected(($tahun)==$th)>{{ $th }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="align-self:end; font-size:0.78rem; color:var(--text-sec); padding-bottom:8px;">
                            <i class="fa-solid fa-calendar"></i> {{ $labelRange ?? '' }}
                        </div>
                    </div>

                    {{-- Mingguan fields --}}
                    <div id="fields-mingguan" class="filter-fields" style="margin-top:14px; grid-template-columns: 1fr auto; gap:12px; display:{{ $jenis==='mingguan' ? 'grid' : 'none' }};">
                        <div class="filter-field">
                            <label class="form-label" for="tanggal">Minggu</label>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <input type="date" name="tanggal" id="tanggal" value="{{ $tanggal }}" class="form-control" style="max-width:220px;">
                                <span style="font-size:0.82rem; color:var(--text-sec); white-space:nowrap;">
                                    <i class="fa-solid fa-calendar-week"></i> <strong>{{ $labelPeriode }}</strong> <span style="font-weight:400;">(Senin–Minggu)</span>
                                </span>
                            </div>
                            <small style="font-size:0.72rem;color:var(--text-sec);">Pilih tanggal dalam minggu, otomatis Senin–Minggu.</small>
                        </div>
                        <div style="align-self:end; font-size:0.78rem; color:var(--text-sec); padding-bottom:8px;">
                            Rentang: <strong>{{ $labelPeriode }}</strong>
                        </div>
                    </div>

                    {{-- Periode fields --}}
                    <div id="fields-periode" class="filter-fields" style="margin-top:14px; grid-template-columns: 1fr 1fr auto; gap:12px; display:{{ $jenis==='periode' ? 'grid' : 'none' }};">
                        <div class="filter-field">
                            <label class="form-label" for="start_date">Dari</label>
                            <input type="date" name="start_date" id="start_date" value="{{ $start_date }}" class="form-control">
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="end_date">Sampai</label>
                            <input type="date" name="end_date" id="end_date" value="{{ $end_date }}" class="form-control">
                        </div>
                        <div style="align-self:end; font-size:0.78rem; color:var(--text-sec); padding-bottom:8px;">
                            <i class="fa-solid fa-calendar"></i> {{ $labelPeriode }}
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-table">
                <div class="card-table-header">
                    <div class="ct-title">
                        <i class="fa-solid fa-users" style="color:var(--primary)"></i>
                        <div>
                            <h3>{{ $labelHeader ?? 'Rekap Absen' }}</h3>
                            <p>
                                @if($jenis==='mingguan')
                                    {{ $labelPeriode }} • {{ $siswaPage->total() }} siswa • {{ $rekap->count() }} pada halaman ini
                                @elseif($jenis==='periode')
                                    {{ $labelPeriode }} • {{ $siswaPage->total() }} siswa • {{ $rekap->count() }} pada halaman ini
                                @else
                                    {{ $labelPeriode }} • {{ $labelRange ?? '' }} • {{ $siswaPage->total() }} siswa • {{ $rekap->count() }} pada halaman ini
                                @endif
                                • {{ $tanggalList->count() }} hari
                            </p>
                        </div>
                    </div>
                    <div class="rekap-export-bar">
                        @if($isLive ?? false)
                            <span class="badge-status" style="background:#FEF3C7; color:#92400E; border:1px solid #FDE68A; font-size:0.75rem;">LIVE</span>
                        @else
                            <span class="badge-status" style="background:#DBEAFE; color:#1E40AF; border:1px solid #BFDBFE; font-size:0.75rem;">ARSIP</span>
                        @endif
                        @if($rekap->count() > 0)
                            <a href="{{ route('rekap.admin.export', request()->query()) }}" class="btn-tool btn-excel" title="Export Excel Kalender"><i class="fa-solid fa-file-excel"></i> Excel</a>
                            <a href="{{ route('rekap.admin.print', request()->query()) }}" target="_blank" class="btn-tool btn-pdf" title="Print / PDF"><i class="fa-solid fa-print"></i> Print</a>
                        @endif
                    </div>
                </div>

                @if($rekap->count() > 0 && $tanggalList->count() > 0)
                    <div class="rekap-calendar-wrap">
                        <table class="rekap-calendar-table">
                            <thead>
                                @if($jenis === 'bulanan')
                                <tr>
                                    <th rowspan="3" class="sticky-col sticky-no">No</th>
                                    <th rowspan="3" class="sticky-col sticky-siswa">Siswa</th>
                                    <th colspan="{{ $tanggalList->count() * 2 }}" style="text-align:center; background:#EAB308; color:#422006;">Tanggal</th>
                                    <th colspan="5" style="text-align:center; background:#0A3A63;" class="sticky-rekap-header">Rekap</th>
                                </tr>
                                <tr>
                                    @foreach($tanggalList as $d)
                                        @php $isW = $d->isWeekend(); @endphp
                                        <th colspan="2" style="min-width:72px; font-size:0.70rem; background:#EAB308; color:#422006;" title="{{ $d->translatedFormat('l, d F Y') }}{{ $isW ? ' (Akhir pekan)' : '' }}">
                                            {{ $d->format('d') }}
                                            <div style="font-weight:400; font-size:0.58rem; opacity:0.9;">{{ $d->translatedFormat('D') }}</div>
                                        </th>
                                    @endforeach
                                    <th rowspan="2" style="min-width:52px; background:#DCFCE7; color:#15803D; border:1px solid #BBF7D0;">Hadir</th>
                                    <th rowspan="2" style="min-width:42px; background:#FFFBEB; color:#92400E; border:1px solid #FDE68A;">Izin</th>
                                    <th rowspan="2" style="min-width:42px; background:#DBEAFE; color:#1E40AF; border:1px solid #BFDBFE;">Sakit</th>
                                    <th rowspan="2" style="min-width:46px; background:#FEE2E2; color:#991B1B; border:1px solid #FECACA;">Alpha</th>
                                    <th rowspan="2" style="min-width:56px; background:#F3E8FF; color:#7E22CE; border:1px solid #E9D5FF;">Laporan</th>
                                </tr>
                                <tr>
                                    @foreach($tanggalList as $d)
                                        <th style="min-width:36px; font-size:0.64rem; background:#0F4C81; color:#fff;">Absen</th>
                                        <th style="min-width:36px; font-size:0.64rem; background:#1D4ED8; color:#fff;">Lap</th>
                                    @endforeach
                                </tr>
                                @else
                                <tr>
                                    <th rowspan="3" class="sticky-col sticky-no">No</th>
                                    <th rowspan="3" class="sticky-col sticky-siswa">Siswa</th>
                                    <th colspan="{{ $tanggalList->count() * 2 }}" style="text-align:center; background:#0F4C81;">Tanggal</th>
                                    <th colspan="5" style="text-align:center; background:#0A3A63;" class="sticky-rekap-header">Rekap</th>
                                </tr>
                                <tr>
                                    @foreach($tanggalList as $d)
                                        @php $isW = $d->isWeekend(); @endphp
                                        <th colspan="2" style="min-width:72px; font-size:0.70rem; {{ $isW ? 'background:#64748B; color:#fff;' : '' }}" title="{{ $d->translatedFormat('l, d F Y') }}{{ $isW ? ' (Akhir pekan)' : '' }}">
                                            {{ $d->format('d') }}
                                            <div style="font-weight:400; font-size:0.58rem; opacity:0.9;">{{ $d->translatedFormat('D') }}</div>
                                        </th>
                                    @endforeach
                                    <th rowspan="2" style="min-width:52px; background:#DCFCE7; color:#15803D; border:1px solid #BBF7D0;">Hadir</th>
                                    <th rowspan="2" style="min-width:42px; background:#FFFBEB; color:#92400E; border:1px solid #FDE68A;">Izin</th>
                                    <th rowspan="2" style="min-width:42px; background:#DBEAFE; color:#1E40AF; border:1px solid #BFDBFE;">Sakit</th>
                                    <th rowspan="2" style="min-width:46px; background:#FEE2E2; color:#991B1B; border:1px solid #FECACA;">Alpha</th>
                                    <th rowspan="2" style="min-width:56px; background:#F3E8FF; color:#7E22CE; border:1px solid #E9D5FF;">Laporan</th>
                                </tr>
                                <tr>
                                    @foreach($tanggalList as $d)
                                        <th style="min-width:36px; font-size:0.64rem; background:#0F4C81; color:#fff;">Absen</th>
                                        <th style="min-width:36px; font-size:0.64rem; background:#1D4ED8; color:#fff;">Lap</th>
                                    @endforeach
                                </tr>
                                @endif
                            </thead>
                            <tbody>
                                @foreach($rekap as $idx => $row)
                                    @php
                                        $s = $row->user;
                                        $uid = $s->id ?? $row->user_id;
                                        $tot = $totalsKalender[$uid] ?? ['hadir'=>($row->jumlah_hadir ?? 0),'izin'=>($row->jumlah_izin ?? 0),'sakit'=>($row->jumlah_sakit ?? 0),'alpha'=>($row->jumlah_alpha ?? 0),'laporan'=>($row->jumlah_laporan ?? 0),'telat'=>0];
                                        $no = $siswaPage->firstItem() + $idx;
                                    @endphp
                                    <tr>
                                        <td class="sticky-col sticky-no" style="text-align:center; font-weight:600; color:var(--text-sec);">{{ $no }}</td>
                                        <td class="sticky-col sticky-siswa">
                                            <div style="display:flex; flex-direction:column; line-height:1.25;">
                                                <span style="font-weight:600; color:var(--text-main); font-size:0.84rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $s->nama ?? $s->username }}">{{ $s->nama ?? $s->username }}</span>
                                                <span style="font-size:0.70rem; color:var(--text-sec); font-family:monospace;">{{ $s->user_id ?? '-' }}</span>
                                            </div>
                                        </td>
                                        @foreach($tanggalList as $d)
                                            @php
                                                $dStr = $d->toDateString();
                                                $cell = $matrix[$uid][$dStr] ?? ['code'=>'-','label'=>'-','color'=>'#9CA3AF','bg'=>'#F8FAFC','border'=>'#E5E7EB','isClickable'=>false,'type'=>'empty'];
                                                $isClickable = $cell['isClickable'] ?? false;
                                                $title = $d->translatedFormat('d F Y') . ' • ' . ($s->nama ?? '') . ' • ' . ($cell['code'] ?? '-') . (isset($cell['note']) && $cell['note'] ? ' ('.$cell['note'].')' : '');
                                                $hasLap = $cell['laporan'] ?? false;
                                                $lapTitle = $d->translatedFormat('d F Y') . ' • ' . ($s->nama ?? '') . ' • Laporan: ' . ($hasLap ? 'OK' : '-');
                                            @endphp
                                            <td>
                                                <span class="rekap-badge-cell {{ $isClickable ? 'clickable' : '' }}"
                                                    @if($isClickable) data-user-id="{{ $uid }}" data-tanggal="{{ $dStr }}" data-nama="{{ $s->nama ?? $s->username }}" @endif
                                                    title="{{ $title }}"
                                                    style="background:{{ $cell['bg'] }}; color:{{ $cell['color'] }}; border:1px solid {{ $cell['border'] ?? $cell['bg'] }};">
                                                    {{ $cell['label'] }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($hasLap)
                                                    <span class="rekap-badge-cell" title="{{ $lapTitle }}"
                                                        style="background:#DBEAFE; color:#1E40AF; border:1px solid #BFDBFE;">OK</span>
                                                @else
                                                    <span class="rekap-badge-cell" title="{{ $lapTitle }}"
                                                        style="background:#F1F5F9; color:#94A3B8; border:1px solid #E2E8F0;">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="rekap-totals-cell" style="background:#F0FDF4; color:#15803D;">{{ $tot['hadir'] }}</td>
                                        <td class="rekap-totals-cell" style="background:#FFFBEB; color:#92400E;">{{ $tot['izin'] }}</td>
                                        <td class="rekap-totals-cell" style="background:#EFF6FF; color:#1E40AF;">{{ $tot['sakit'] }}</td>
                                        <td class="rekap-totals-cell" style="background:#FEF2F2; color:#991B1B;">{{ $tot['alpha'] }}</td>
                                        <td class="rekap-totals-cell" style="background:#FAF5FF; color:#7E22CE;">{{ $tot['laporan'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="rekap-legend">
                        <strong style="color:var(--text-main); font-size:0.78rem;"><i class="fa-solid fa-circle-info"></i> Keterangan:</strong>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#DCFCE7;color:#15803D;border-color:#BBF7D0;">H</span> Hadir</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#FEF3C7;color:#92400E;border-color:#FDE68A;">T</span> Telat</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#FFFBEB;color:#92400E;border-color:#FDE68A;">I</span> Izin/Dispensasi</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#DBEAFE;color:#1E40AF;border-color:#BFDBFE;">S</span> Sakit</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#FEE2E2;color:#991B1B;border-color:#FECACA;">A</span> Alpha</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#F1F5F9;color:#64748B;border-color:#E2E8F0;">-</span> Libur</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#DBEAFE;color:#1E40AF;border-color:#BFDBFE;">OK</span> Laporan ada</span>
                    </div>

                    <div class="table-footer">
                        <span>Menampilkan {{ $siswaPage->firstItem() }}–{{ $siswaPage->lastItem() }} dari {{ $siswaPage->total() }} siswa ({{ $labelPeriode }}) • Geser tabel ke kanan untuk melihat semua tanggal</span>
                        <div class="pagination-links">{{ $siswaPage->links('vendor.pagination.custom') }}</div>
                    </div>
                @else
                    <div class="table-empty" style="padding:44px 20px;">
                        <i class="fa-solid fa-magnifying-glass" style="font-size:2.2rem;"></i>
                        <div style="font-weight:600; margin-top:8px;">Tidak ada siswa ditemukan.</div>
                        <div style="font-size:0.82rem; color:var(--text-sec); margin-top:4px;">Coba ubah kata kunci pencarian atau filter lain.</div>
                        <a href="{{ route('rekap.admin') }}" class="btn-tool" style="margin-top:12px; display:inline-flex;"><i class="fa-solid fa-rotate-left"></i> Reset Filter</a>
                    </div>
                @endif
            </div>

            <footer class="footer">
                <span>© 2026 PT Sandya Network</span>
                <span class="footer-sep">•</span>
                <span>Sistem Absensi PKL</span>
            </footer>
        </main>
    </div>
</div>

{{-- Modal Detail --}}
<div class="modal-overlay" id="rekapDetailModal" style="z-index:1600;">
    <div class="modal modal-lg" style="max-width:560px;">
        <div class="modal-header">
            <h3 id="rekapDetailTitle" style="font-size:1.02rem;">Detail Absensi</h3>
            <button class="modal-close" onclick="closeRekapDetail()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="rekapDetailBody" style="min-height:120px;">
            <div style="display:flex; align-items:center; justify-content:center; padding:30px; color:var(--text-sec); gap:10px;"><div class="spinner"></div> Memuat detail...</div>
        </div>
        <div class="modal-footer">
            <button class="btn-modal-cancel" onclick="closeRekapDetail()">Tutup</button>
        </div>
    </div>
</div>

<script>
    (function(){
        const jenisEl = document.getElementById('jenis');
        const fBulanan = document.getElementById('fields-bulanan');
        const fMingguan = document.getElementById('fields-mingguan');
        const fPeriode = document.getElementById('fields-periode');
        function toggle(){
            const v = jenisEl.value;
            if(fBulanan) fBulanan.style.display = v==='bulanan' ? 'grid' : 'none';
            if(fMingguan) fMingguan.style.display = v==='mingguan' ? 'grid' : 'none';
            if(fPeriode) fPeriode.style.display = v==='periode' ? 'grid' : 'none';
            if(fBulanan){ fBulanan.querySelectorAll('select,input').forEach(el=> el.disabled = v!=='bulanan'); }
            if(fMingguan){ fMingguan.querySelectorAll('input').forEach(el=> el.disabled = v!=='mingguan'); }
            if(fPeriode){ fPeriode.querySelectorAll('input').forEach(el=> el.disabled = v!=='periode'); }
        }
        if(jenisEl){ jenisEl.addEventListener('change', toggle); toggle(); }

        // Klik sel kalender
        const modal = document.getElementById('rekapDetailModal');
        const body = document.getElementById('rekapDetailBody');
        const title = document.getElementById('rekapDetailTitle');
        function esc(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
        window.closeRekapDetail = function(){ if(modal) modal.classList.remove('show'); };
        if(modal){
            modal.addEventListener('click', function(e){ if(e.target===modal) closeRekapDetail(); });
            document.addEventListener('keydown', function(e){ if(e.key==='Escape' && modal.classList.contains('show')) closeRekapDetail(); });
        }
        document.querySelectorAll('.rekap-badge-cell.clickable').forEach(function(el){
            el.addEventListener('click', function(){
                const uid = this.getAttribute('data-user-id');
                const tgl = this.getAttribute('data-tanggal');
                const nama = this.getAttribute('data-nama') || 'Siswa';
                if(!uid || !tgl) return;
                title.textContent = nama + ' • ' + tgl;
                body.innerHTML = '<div style="display:flex; align-items:center; justify-content:center; padding:30px; color:var(--text-sec); gap:10px;"><div class="spinner"></div> Memuat detail...</div>';
                modal.classList.add('show');
                fetch('{{ route('rekap.detail') }}?user_id=' + encodeURIComponent(uid) + '&tanggal=' + encodeURIComponent(tgl), {
                    headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' }
                })
                .then(r=> r.json())
                .then(data=>{
                    if(data.error){ body.innerHTML = '<div style="padding:18px; text-align:center; color:#991B1B;">'+esc(data.error)+'</div>'; return; }
                    let html = '';
                    html += '<div style="background:#F8FAFC; padding:12px 14px; border-radius:10px; margin-bottom:12px; border:1px solid var(--border);">';
                    html += '<div style="font-size:0.78rem; color:var(--text-sec);">'+esc(data.hari)+'</div>';
                    // badge color: handle belum_pulang (•) as yellow
                    let badgeBg = '#F1F5F9', badgeColor = '#64748B';
                    if(data.code==='H') { badgeBg='#DCFCE7'; badgeColor='#15803D'; }
                    else if(data.code==='T') { badgeBg='#FEF3C7'; badgeColor='#92400E'; }
                    else if(data.code==='I') { badgeBg='#FFFBEB'; badgeColor='#92400E'; }
                    else if(data.code==='S') { badgeBg='#DBEAFE'; badgeColor='#1E40AF'; }
                    else if(data.code==='A') { badgeBg='#FEE2E2'; badgeColor='#991B1B'; }
                    else if(data.type==='belum_pulang' || data.code==='•') { badgeBg='#FFFBEB'; badgeColor='#B45309'; }
                    html += '<div style="font-weight:700; color:var(--text-main); display:flex; gap:8px; align-items:center; flex-wrap:wrap;">'+esc(data.nama)+' <span style="font-family:monospace; font-weight:400; font-size:0.78rem; color:var(--text-sec);">'+esc(data.user_id)+'</span> <span style="background:'+badgeBg+'; color:'+badgeColor+'; padding:3px 10px; border-radius:999px; font-size:0.78rem;">'+esc(data.code)+' — '+esc(data.status||'')+'</span></div>';
                    html += '<div style="font-size:0.82rem; color:var(--text-sec); margin-top:4px;"><i class="fa-solid fa-calendar"></i> '+esc(data.tanggal)+'</div>';
                    html += '</div>';
                    if(data.code==='A'){
                        html += '<div style="padding:14px; background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; color:#991B1B; font-weight:600; text-align:center;"><i class="fa-solid fa-user-xmark"></i> '+esc(data.message)+'</div>';
                    } else if(data.type==='belum_pulang'){
                        html += '<div style="padding:14px; background:#FFFBEB; border:1px solid #FDE68A; border-radius:10px; color:#92400E; font-weight:600; text-align:center;"><i class="fa-solid fa-clock"></i> '+esc(data.message||'Belum Pulang - batas pulang belum terlewati')+'</div>';
                        if(data.jam_masuk){
                            html += '<div class="detail-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:10px 18px; margin-bottom:14px; margin-top:12px;">';
                            html += '<div class="detail-row" style="display:flex; justify-content:space-between; gap:10px; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span class="detail-label" style="font-size:0.78rem; color:var(--text-sec);">Jam Masuk</span><span class="detail-value" style="font-weight:600;">'+esc(data.jam_masuk||'-')+'</span></div>';
                            html += '<div class="detail-row" style="display:flex; justify-content:space-between; gap:10px; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span class="detail-label" style="font-size:0.78rem; color:var(--text-sec);">Jam Pulang</span><span class="detail-value" style="font-weight:600;">'+esc(data.jam_pulang||'-')+'</span></div>';
                            html += '<div class="detail-row" style="display:flex; justify-content:space-between; gap:10px; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span class="detail-label" style="font-size:0.78rem; color:var(--text-sec);">Status Lokasi</span><span class="detail-value" style="font-weight:600;">'+esc(data.status_lokasi||'-')+'</span></div>';
                            html += '<div class="detail-row" style="display:flex; justify-content:space-between; gap:10px; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span class="detail-label" style="font-size:0.78rem; color:var(--text-sec);">Jarak / Radius</span><span class="detail-value" style="font-weight:600;">'+(data.jarak? esc(data.jarak)+'m' : '-')+' / '+(data.radius? esc(data.radius)+'m':'-')+'</span></div>';
                            if(data.keterangan && data.keterangan!=='-'){
                                html += '<div class="detail-row" style="grid-column:1/-1; display:flex; justify-content:space-between; gap:10px; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span class="detail-label" style="font-size:0.78rem; color:var(--text-sec);">Keterangan</span><span class="detail-value" style="font-weight:600; text-align:right; word-break:break-word;">'+esc(data.keterangan)+'</span></div>';
                            }
                            html += '</div>';
                            if(data.bukti_url){
                                const isPdf = (data.bukti_mime && data.bukti_mime.includes('pdf')) || (data.bukti_name && data.bukti_name.toLowerCase().endsWith('.pdf'));
                                html += '<div style="margin-bottom:10px;"><div style="font-size:0.78rem; font-weight:600; color:var(--text-main); margin-bottom:6px;"><i class="fa-solid fa-paperclip"></i> Bukti Pendukung</div>';
                                if(isPdf) html += '<a href="'+esc(data.bukti_url)+'" target="_blank" class="btn-tool" style="display:inline-flex;"><i class="fa-solid fa-file-pdf"></i> Lihat PDF ('+esc(data.bukti_name||'bukti')+')</a>';
                                else html += '<a href="'+esc(data.bukti_url)+'" target="_blank"><img src="'+esc(data.bukti_url)+'" alt="Bukti" style="max-width:100%; max-height:180px; border-radius:10px; border:1px solid var(--border);"></a><div style="font-size:0.72rem; color:var(--text-sec); margin-top:4px;">'+esc(data.bukti_name||'')+'</div>';
                                html += '</div>';
                            }
                            if(data.foto_url) html += '<div style="margin-bottom:6px;"><div style="font-size:0.78rem; font-weight:600; color:var(--text-main); margin-bottom:6px;"><i class="fa-solid fa-camera"></i> Foto Absen</div><a href="'+esc(data.foto_url)+'" target="_blank"><img src="'+esc(data.foto_url)+'" alt="Foto" style="max-width:100%; max-height:180px; border-radius:10px; border:1px solid var(--border);"></a></div>';
                        }
                    } else if(['weekend','future','before','pending'].includes(data.type)){
                        html += '<div style="padding:14px; background:#F8FAFC; border:1px solid var(--border); border-radius:10px; color:var(--text-sec); text-align:center;">'+esc(data.message||data.status)+'</div>';
                    } else if(data.jam_masuk){
                        html += '<div class="detail-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:10px 18px; margin-bottom:14px;">';
                        html += '<div class="detail-row" style="display:flex; justify-content:space-between; gap:10px; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span class="detail-label" style="font-size:0.78rem; color:var(--text-sec);">Jam Masuk</span><span class="detail-value" style="font-weight:600;">'+esc(data.jam_masuk||'-')+'</span></div>';
                        html += '<div class="detail-row" style="display:flex; justify-content:space-between; gap:10px; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span class="detail-label" style="font-size:0.78rem; color:var(--text-sec);">Jam Pulang</span><span class="detail-value" style="font-weight:600;">'+esc(data.jam_pulang||'-')+'</span></div>';
                        html += '<div class="detail-row" style="display:flex; justify-content:space-between; gap:10px; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span class="detail-label" style="font-size:0.78rem; color:var(--text-sec);">Status Lokasi</span><span class="detail-value" style="font-weight:600;">'+esc(data.status_lokasi||'-')+'</span></div>';
                        html += '<div class="detail-row" style="display:flex; justify-content:space-between; gap:10px; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span class="detail-label" style="font-size:0.78rem; color:var(--text-sec);">Jarak / Radius</span><span class="detail-value" style="font-weight:600;">'+(data.jarak? esc(data.jarak)+'m' : '-')+' / '+(data.radius? esc(data.radius)+'m':'-')+'</span></div>';
                        if(data.keterangan && data.keterangan!=='-'){
                            html += '<div class="detail-row" style="grid-column:1/-1; display:flex; justify-content:space-between; gap:10px; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span class="detail-label" style="font-size:0.78rem; color:var(--text-sec);">Keterangan</span><span class="detail-value" style="font-weight:600; text-align:right; word-break:break-word;">'+esc(data.keterangan)+'</span></div>';
                        }
                        html += '</div>';
                        let buktiHtml = '';
                        if(data.bukti_url){
                            const isPdf = (data.bukti_mime && data.bukti_mime.includes('pdf')) || (data.bukti_name && data.bukti_name.toLowerCase().endsWith('.pdf'));
                            buktiHtml += '<div style="margin-bottom:10px;"><div style="font-size:0.78rem; font-weight:600; color:var(--text-main); margin-bottom:6px;"><i class="fa-solid fa-paperclip"></i> Bukti Pendukung</div>';
                            if(isPdf){
                                buktiHtml += '<a href="'+esc(data.bukti_url)+'" target="_blank" class="btn-tool" style="display:inline-flex;"><i class="fa-solid fa-file-pdf"></i> Lihat PDF ('+esc(data.bukti_name||'bukti')+')</a>';
                            } else {
                                buktiHtml += '<a href="'+esc(data.bukti_url)+'" target="_blank"><img src="'+esc(data.bukti_url)+'" alt="Bukti" style="max-width:100%; max-height:180px; border-radius:10px; border:1px solid var(--border);"></a><div style="font-size:0.72rem; color:var(--text-sec); margin-top:4px;">'+esc(data.bukti_name||'')+'</div>';
                            }
                            buktiHtml += '</div>';
                        }
                        html += buktiHtml;
                        if(data.foto_url){
                            html += '<div style="margin-bottom:6px;"><div style="font-size:0.78rem; font-weight:600; color:var(--text-main); margin-bottom:6px;"><i class="fa-solid fa-camera"></i> Foto Absen</div><a href="'+esc(data.foto_url)+'" target="_blank"><img src="'+esc(data.foto_url)+'" alt="Foto" style="max-width:100%; max-height:180px; border-radius:10px; border:1px solid var(--border);"></a></div>';
                        }
                    } else {
                        html += '<div style="padding:14px; background:#F8FAFC; border:1px solid var(--border); border-radius:10px; color:var(--text-sec); text-align:center;">Tidak ada detail absensi.</div>';
                    }
                    body.innerHTML = html;
                })
                .catch(err=>{
                    body.innerHTML = '<div style="padding:18px; text-align:center; color:#991B1B;">Gagal memuat detail.<br><span style="font-size:0.78rem; color:var(--text-sec);">'+esc(err.message||'')+'</span></div>';
                });
            });
        });
    })();
</script>
<script src="{{ asset('js/dashboard.js') }}"></script>
@if(auth()->check() && auth()->user()->role === 'admin')
<script src="{{ asset('js/admin-notifikasi.js') }}"></script>
@endif
</body>
</html>
