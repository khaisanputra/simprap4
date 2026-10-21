@php
    $role = $role ?? 'siswa';
    $filterTahun = $filterTahun ?? null;
    $jenis = $jenis ?? 'bulanan';
    $start_date = $start_date ?? ($start->toDateString() ?? now()->toDateString());
    $end_date = $end_date ?? ($end->toDateString() ?? now()->toDateString());
    $tanggal = $tanggal ?? $start_date;
    $tanggalListSiswa = $tanggalListSiswa ?? collect();
    $matrixSiswa = $matrixSiswa ?? [];
    $totalsSiswa = $totalsSiswa ?? [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absen — Sandya Absensi PKL</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    <style>
        .rekap-calendar-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; border-top:1px solid var(--border); }
        .rekap-calendar-table { width:max-content; min-width:100%; border-collapse:collapse; }
        .rekap-calendar-table th, .rekap-calendar-table td { border-bottom:1px solid #F1F5F9; white-space:nowrap; }
        .rekap-calendar-table thead th { background:var(--primary); color:#fff; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.03em; padding:10px 6px; text-align:center; }
        .rekap-calendar-table tbody td { padding:7px 6px; font-size:0.82rem; text-align:center; }
        .rekap-calendar-table tbody tr:hover td { background:#F8FAFC; }
        .sticky-col { position:sticky; background:#fff; }
        .sticky-no { left:0; min-width:42px; max-width:42px; z-index:3; border-right:1px solid #E2E8F0; }
        .sticky-siswa { left:42px; min-width:200px; max-width:200px; text-align:left !important; padding-left:12px !important; z-index:3; border-right:1px solid #E2E8F0; }
        .rekap-calendar-table thead th.sticky-col { background:var(--primary); z-index:4; }
        .rekap-badge-cell { display:inline-flex; align-items:center; justify-content:center; min-width:26px; height:26px; padding:0 6px; border-radius:7px; font-size:0.75rem; font-weight:700; line-height:1; }
        .rekap-badge-cell.clickable { cursor:pointer; transition:transform 0.15s, box-shadow 0.15s; }
        .rekap-badge-cell.clickable:hover { transform:scale(1.08); box-shadow:0 2px 8px rgba(0,0,0,0.12); }
        .rekap-legend { display:flex; flex-wrap:wrap; gap:10px; padding:12px 16px; border-top:1px solid var(--border); background:#FBFDFF; font-size:0.74rem; color:var(--text-sec); align-items:center; }
        .rekap-legend-item { display:inline-flex; align-items:center; gap:6px; }
        .rekap-legend-dot { width:18px; height:18px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; font-size:0.66rem; font-weight:700; border:1px solid transparent; }
        @media (max-width:640px){
            .rekap-calendar-wrap { margin:0 -14px; padding:0 14px; }
            .sticky-siswa { min-width:150px; max-width:150px; }
        }
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
            <a href="{{ route('rekap.siswa') }}" class="nav-link active" data-tooltip="Rekap Absen"><i class="fa-solid fa-chart-column"></i><span>Rekap Absen</span></a>
            <p class="nav-section">Lainnya</p>
            @if($role==='admin')
                <a href="{{ route('data-absen-masuk.index') }}" class="nav-link" data-tooltip="Data Absen"><i class="fa-solid fa-clipboard-check"></i><span>Data Absen</span></a>
                <a href="{{ route('rekap.admin') }}" class="nav-link" data-tooltip="Rekap Absen"><i class="fa-solid fa-chart-column"></i><span>Rekap Absen</span></a>
                <a href="{{ route('users.index') }}" class="nav-link" data-tooltip="Menu User"><i class="fa-solid fa-users"></i><span>Menu User</span></a>
                <a href="{{ route('lokasi-kantor.index') }}" class="nav-link" data-tooltip="Pengaturan Lokasi"><i class="fa-solid fa-location-dot"></i><span>Pengaturan Lokasi</span></a>
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
                <div class="navbar-title">
                    <span class="nb-title-main">Rekap Absen</span>
                    <span class="nb-title-sub">{{ $labelHeader ?? 'Rekap Absen' }} • {{ $labelPeriode ?? '' }} @if($isLive ?? false) <span style="background:#DCFCE7;color:#15803D;padding:2px 8px;border-radius:999px;font-size:0.70rem;font-weight:700;">LIVE</span> @else <span style="background:#DBEAFE;color:#1E40AF;padding:2px 8px;border-radius:999px;font-size:0.70rem;font-weight:700;">ARSIP</span> @endif</span>
                </div>
            </div>
            <div class="navbar-right">
                <div class="profile-area">
                    <div class="avatar"><img src="https://i.pravatar.cc/100?img=12" alt="Foto"></div>
                    <div class="profile-meta">
                        <span class="profile-name">{{ $username ?? 'Siswa' }}</span>
                        <span class="profile-role">{{ $role==='admin'?'Administrator':'Siswa PKL' }}</span>
                    </div>
                    <button class="icon-btn" id="profileDropdownBtn"><i class="fa-solid fa-chevron-down"></i></button>
                    <div class="dropdown-menu" id="profileDropdown">
                        @if($role==='admin')
                            <a href="{{ route('users.index') }}" class="dropdown-item"><i class="fa-solid fa-users"></i> Menu User</a>
                            <div class="dropdown-divider"></div>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item dropdown-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</button></form>
                    </div>
                </div>
            </div>
        </header>

        <main class="page-content">
            <div class="page-title" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
                <div>
                    <h1 class="page-title-main">Rekap Absen</h1>
                    <p class="page-title-sub">
                        @if($jenis==='mingguan') Rekap Mingguan • <strong>{{ $labelPeriode }}</strong>
                        @elseif($jenis==='periode') Rekap Periode • <strong>{{ $labelPeriode }}</strong>
                        @else Rekap Bulanan • <strong>{{ $labelPeriode }}</strong> • <span style="font-size:0.82rem;color:var(--text-sec);">{{ $labelRange ?? '' }}</span>
                        @endif
                    </p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn-tool" style="height:40px;"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
            </div>

            <div class="card-table filter-form" style="margin-bottom:18px;">
                @if(isset($errors) && $errors->any())
                    <div class="alert alert-danger" style="margin:12px 22px 0 22px; background:#FEF2F2; border:1px solid #FECACA; color:#991B1B; padding:10px 14px; border-radius:8px;">
                        <ul style="margin:0;padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif
                <form method="GET" action="{{ route('rekap.siswa') }}" id="rekapSiswaForm" style="padding:16px 20px;">
                    <div class="filter-fields" style="grid-template-columns: 0.9fr auto; gap:12px; display:grid;">
                        <div class="filter-field">
                            <label class="form-label" for="jenis">Jenis Rekap</label>
                            <select name="jenis" id="jenisSiswa" class="form-control filter-select">
                                <option value="mingguan" @selected($jenis==='mingguan')>Mingguan</option>
                                <option value="bulanan" @selected($jenis==='bulanan')>Bulanan</option>
                                <option value="periode" @selected($jenis==='periode')>Periode</option>
                            </select>
                        </div>
                        <div class="filter-buttons" style="align-self:end;">
                            <button type="submit" class="btn-tool" style="background:var(--primary); color:#fff; border-color:var(--primary);"><i class="fa-solid fa-magnifying-glass"></i> Tampilkan</button>
                            <a href="{{ route('rekap.siswa') }}" class="btn-tool" title="Reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                        </div>
                    </div>
                    <div id="fields-siswa-bulanan" class="filter-fields" style="margin-top:14px; grid-template-columns: 0.7fr 0.7fr auto; gap:12px; display:{{ $jenis==='bulanan' ? 'grid' : 'none' }};">
                        <div class="filter-field">
                            <label class="form-label" for="bulan">Bulan</label>
                            <select name="bulan" id="bulanSiswa" class="form-control filter-select">
                                @for($m=1;$m<=12;$m++) <option value="{{ $m }}" @selected(($bulan??now()->format('n'))==$m)>{{ \App\Models\MonthlyStatistic::namaBulan($m) }}</option> @endfor
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="tahun">Tahun</label>
                            <select name="tahun" id="tahunSiswa" class="form-control filter-select">
                                @foreach($tahunList as $th) <option value="{{ $th }}" @selected(($tahun)==$th)>{{ $th }}</option> @endforeach
                            </select>
                        </div>
                        <div style="align-self:end; font-size:0.78rem; color:var(--text-sec); padding-bottom:8px;"><i class="fa-solid fa-calendar"></i> {{ $labelRange ?? '' }}</div>
                    </div>
                    <div id="fields-siswa-mingguan" class="filter-fields" style="margin-top:14px; grid-template-columns: 1fr auto; gap:12px; display:{{ $jenis==='mingguan' ? 'grid' : 'none' }};">
                        <div class="filter-field">
                            <label class="form-label" for="tanggal">Minggu</label>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <input type="date" name="tanggal" id="tanggalSiswa" value="{{ $tanggal }}" class="form-control" style="max-width:220px;">
                                <span style="font-size:0.82rem; color:var(--text-sec); white-space:nowrap;"><i class="fa-solid fa-calendar-week"></i> <strong>{{ $labelPeriode }}</strong> (Senin–Minggu)</span>
                            </div>
                        </div>
                        <div style="align-self:end; font-size:0.78rem; color:var(--text-sec); padding-bottom:8px;">Rentang: <strong>{{ $labelPeriode }}</strong></div>
                    </div>
                    <div id="fields-siswa-periode" class="filter-fields" style="margin-top:14px; grid-template-columns: 1fr 1fr auto; gap:12px; display:{{ $jenis==='periode' ? 'grid' : 'none' }};">
                        <div class="filter-field">
                            <label class="form-label" for="start_date">Dari</label>
                            <input type="date" name="start_date" id="start_dateSiswa" value="{{ $start_date }}" class="form-control">
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="end_date">Sampai</label>
                            <input type="date" name="end_date" id="end_dateSiswa" value="{{ $end_date }}" class="form-control">
                        </div>
                        <div style="align-self:end; font-size:0.78rem; color:var(--text-sec); padding-bottom:8px;"><i class="fa-solid fa-calendar"></i> {{ $labelPeriode }}</div>
                    </div>
                </form>
            </div>

            {{-- Rekap Kalender untuk periode terpilih --}}
            <div class="card-table" style="margin-bottom:18px;">
                <div class="card-table-header">
                    <div class="ct-title">
                        <i class="fa-solid fa-user-check" style="color:var(--primary)"></i>
                        <div>
                            <h3>{{ $labelHeader ?? 'Rekap Absen' }} • {{ $labelPeriode }}</h3>
                            <p>Rekap milik <strong>{{ $user->nama ?? $username }}</strong> ({{ $user->user_id ?? '-' }}) • {{ $tanggalListSiswa->count() }} hari</p>
                        </div>
                    </div>
                    @if($isLive ?? false) <span class="badge-status" style="background:#FEF3C7; color:#92400E; border:1px solid #FDE68A; font-size:0.75rem;">LIVE</span> @else <span class="badge-status" style="background:#DBEAFE; color:#1E40AF; border:1px solid #BFDBFE; font-size:0.75rem;">ARSIP</span> @endif
                </div>

                @php
                    $uidSiswa = $user->id;
                    $totSiswa = $totalsSiswa[$uidSiswa] ?? ['hadir'=>($rekap->jumlah_hadir??0),'izin'=>($rekap->jumlah_izin??0),'sakit'=>($rekap->jumlah_sakit??0),'alpha'=>($rekap->jumlah_alpha??0),'laporan'=>($rekap->jumlah_laporan??0)];
                @endphp

                @if($tanggalListSiswa->count() > 0)
                    <div class="rekap-calendar-wrap">
                        <table class="rekap-calendar-table">
                            <thead>
                                @if($jenis === 'bulanan')
                                <tr>
                                    <th rowspan="3" style="min-width:42px;" class="sticky-col sticky-no">No</th>
                                    <th rowspan="3" style="min-width:180px;" class="sticky-col sticky-siswa">Siswa</th>
                                    <th colspan="{{ $tanggalListSiswa->count() * 2 }}" style="text-align:center; background:#EAB308; color:#422006;">Tanggal</th>
                                    <th colspan="5" style="text-align:center; background:#0A3A63;">Rekap</th>
                                </tr>
                                <tr>
                                    @foreach($tanggalListSiswa as $d)
                                        @php $isW = $d->isWeekend(); @endphp
                                        <th colspan="2" style="min-width:72px; font-size:0.70rem; background:#EAB308; color:#422006;" title="{{ $d->translatedFormat('l, d F Y') }}{{ $isW ? ' (Akhir pekan)' : '' }}">
                                            {{ $d->format('d') }}
                                            <div style="font-weight:400; font-size:0.58rem; opacity:0.9;">{{ $d->translatedFormat('D') }}</div>
                                        </th>
                                    @endforeach
                                    <th rowspan="2" style="min-width:52px; background:#DCFCE7; color:#15803D;">Hadir</th>
                                    <th rowspan="2" style="min-width:42px; background:#FFFBEB; color:#92400E;">Izin</th>
                                    <th rowspan="2" style="min-width:42px; background:#DBEAFE; color:#1E40AF;">Sakit</th>
                                    <th rowspan="2" style="min-width:46px; background:#FEE2E2; color:#991B1B;">Alpha</th>
                                    <th rowspan="2" style="min-width:56px; background:#F3E8FF; color:#7E22CE;">Laporan</th>
                                </tr>
                                <tr>
                                    @foreach($tanggalListSiswa as $d)
                                        <th style="min-width:36px; font-size:0.64rem; background:#0F4C81; color:#fff;">Absen</th>
                                        <th style="min-width:36px; font-size:0.64rem; background:#1D4ED8; color:#fff;">Lap</th>
                                    @endforeach
                                </tr>
                                @else
                                <tr>
                                    <th rowspan="3" style="min-width:42px;" class="sticky-col sticky-no">No</th>
                                    <th rowspan="3" style="min-width:180px;" class="sticky-col sticky-siswa">Siswa</th>
                                    <th colspan="{{ $tanggalListSiswa->count() * 2 }}" style="text-align:center; background:#0F4C81; color:#fff;">Tanggal</th>
                                    <th colspan="5" style="text-align:center; background:#0A3A63; color:#fff;">Rekap</th>
                                </tr>
                                <tr>
                                    @foreach($tanggalListSiswa as $d)
                                        @php $isW = $d->isWeekend(); @endphp
                                        <th colspan="2" style="min-width:72px; {{ $isW ? 'background:#64748B;' : '' }}" title="{{ $d->translatedFormat('l, d F Y') }}">
                                            {{ $d->format('d') }}
                                            <div style="font-weight:400; font-size:0.58rem; opacity:0.9;">{{ $d->translatedFormat('D') }}</div>
                                        </th>
                                    @endforeach
                                    <th rowspan="2" style="min-width:52px; background:#DCFCE7; color:#15803D;">Hadir</th>
                                    <th rowspan="2" style="min-width:42px; background:#FFFBEB; color:#92400E;">Izin</th>
                                    <th rowspan="2" style="min-width:42px; background:#DBEAFE; color:#1E40AF;">Sakit</th>
                                    <th rowspan="2" style="min-width:46px; background:#FEE2E2; color:#991B1B;">Alpha</th>
                                    <th rowspan="2" style="min-width:56px; background:#F3E8FF; color:#7E22CE;">Laporan</th>
                                </tr>
                                <tr>
                                    @foreach($tanggalListSiswa as $d)
                                        <th style="min-width:36px; font-size:0.64rem; background:#0F4C81; color:#fff;">Absen</th>
                                        <th style="min-width:36px; font-size:0.64rem; background:#1D4ED8; color:#fff;">Lap</th>
                                    @endforeach
                                </tr>
                                @endif
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="sticky-col sticky-no" style="text-align:center; font-weight:600;">1</td>
                                    <td class="sticky-col sticky-siswa">
                                        <div style="display:flex; flex-direction:column; line-height:1.25;">
                                            <span style="font-weight:600; color:var(--text-main); font-size:0.84rem;">{{ $user->nama ?? $username }}</span>
                                            <span style="font-size:0.70rem; color:var(--text-sec); font-family:monospace;">{{ $user->user_id ?? '-' }}</span>
                                        </div>
                                    </td>
                                    @foreach($tanggalListSiswa as $d)
                                        @php
                                            $dStr = $d->toDateString();
                                            $cell = $matrixSiswa[$uidSiswa][$dStr] ?? ['code'=>'-','label'=>'-','color'=>'#9CA3AF','bg'=>'#F8FAFC','border'=>'#E5E7EB','isClickable'=>false];
                                            $isClickable = $cell['isClickable'] ?? false;
                                        @endphp
                                        <td>
                                            <span class="rekap-badge-cell {{ $isClickable ? 'clickable' : '' }}"
                                                @if($isClickable) data-user-id="{{ $uidSiswa }}" data-tanggal="{{ $dStr }}" @endif
                                                title="{{ $d->translatedFormat('d F Y') }} • {{ $cell['code'] }}{{ isset($cell['note']) ? ' ('.$cell['note'].')':'' }}"
                                                style="background:{{ $cell['bg'] }}; color:{{ $cell['color'] }}; border:1px solid {{ $cell['border'] ?? $cell['bg'] }};">
                                                {{ $cell['label'] }}
                                            </span>
                                        </td>
                                        @php $hasLap = $cell['laporan'] ?? false; @endphp
                                        <td>
                                            @if($hasLap)
                                                <span class="rekap-badge-cell" title="{{ $d->translatedFormat('d F Y') }} • Laporan: Ada" style="background:#DBEAFE; color:#1E40AF; border:1px solid #BFDBFE;">✓</span>
                                            @else
                                                <span class="rekap-badge-cell" title="{{ $d->translatedFormat('d F Y') }} • Laporan: Tidak ada" style="background:#F1F5F9; color:#94A3B8; border:1px solid #E2E8F0;">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td style="font-weight:700; background:#F0FDF4; color:#15803D;">{{ $totSiswa['hadir'] }}</td>
                                    <td style="font-weight:700; background:#FFFBEB; color:#92400E;">{{ $totSiswa['izin'] }}</td>
                                    <td style="font-weight:700; background:#EFF6FF; color:#1E40AF;">{{ $totSiswa['sakit'] }}</td>
                                    <td style="font-weight:700; background:#FEF2F2; color:#991B1B;">{{ $totSiswa['alpha'] }}</td>
                                    <td style="font-weight:700; background:#FAF5FF; color:#7E22CE;">{{ $totSiswa['laporan'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="rekap-legend">
                        <strong style="color:var(--text-main);">Keterangan:</strong>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#DCFCE7;color:#15803D;border-color:#BBF7D0;">H</span> Hadir</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#FEF3C7;color:#92400E;border-color:#FDE68A;">T</span> Telat</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#FFFBEB;color:#92400E;border-color:#FDE68A;">I</span> Izin/Dispensasi</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#DBEAFE;color:#1E40AF;border-color:#BFDBFE;">S</span> Sakit</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#FEE2E2;color:#991B1B;border-color:#FECACA;">A</span> Alpha</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#F1F5F9;color:#64748B;">-</span> Libur</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#DBEAFE;color:#1E40AF;border-color:#BFDBFE;">✓</span> Laporan ada</span>
                        <span class="rekap-legend-item"><span class="rekap-legend-dot" style="background:#F1F5F9;color:#94A3B8;border:1px solid #E2E8F0;">—</span> Laporan tidak ada</span>
                    </div>
                @endif
            </div>

            <div class="card-table">
                <div class="card-table-header">
                    <div class="ct-title">
                        <i class="fa-solid fa-clock-rotate-left" style="color:var(--primary)"></i>
                        <div>
                            <h3>Arsip Bulanan</h3>
                            <p>{{ $arsip->total() }} bulan tersimpan (history)</p>
                        </div>
                    </div>
                </div>

                @if($arsip->count() > 0)
                    <div class="table-wrap rekap-table-wrap">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th style="text-align:center;">Hadir</th>
                                    <th style="text-align:center;">Izin</th>
                                    <th style="text-align:center;">Sakit</th>
                                    <th style="text-align:center;">Alpha</th>
                                    <th style="text-align:center;">Laporan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($arsip as $row)
                                    <tr>
                                        <td style="font-weight:600; white-space:nowrap;">{{ $row->label_bulan_tahun }}</td>
                                        <td style="text-align:center;">{{ $row->jumlah_hadir ?? 0 }}</td>
                                        <td style="text-align:center;">{{ $row->jumlah_izin ?? 0 }}</td>
                                        <td style="text-align:center;">{{ $row->jumlah_sakit ?? 0 }}</td>
                                        <td style="text-align:center;">{{ $row->jumlah_alpha ?? $row->jumlah_tidak_masuk ?? 0 }}</td>
                                        <td style="text-align:center;">{{ $row->jumlah_laporan ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="rekap-mobile-list">
                        @foreach($arsip as $row)
                            <div class="rekap-card rekap-card--arsip">
                                <div class="rekap-card-head">
                                    <span class="rekap-card-bulan">{{ $row->label_bulan_tahun }}</span>
                                </div>
                                <div class="rekap-card-grid">
                                    <span>Hadir: <strong>{{ $row->jumlah_hadir ?? 0 }}</strong></span>
                                    <span>Izin: <strong>{{ $row->jumlah_izin ?? 0 }}</strong></span>
                                    <span>Sakit: <strong>{{ $row->jumlah_sakit ?? 0 }}</strong></span>
                                    <span>Alpha: <strong>{{ $row->jumlah_alpha ?? $row->jumlah_tidak_masuk ?? 0 }}</strong></span>
                                    <span>Laporan: <strong>{{ $row->jumlah_laporan ?? 0 }}</strong></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="table-footer">
                        <span>Menampilkan {{ $arsip->firstItem() }}–{{ $arsip->lastItem() }} dari {{ $arsip->total() }} arsip</span>
                        <div class="pagination-links">{{ $arsip->links('vendor.pagination.custom') }}</div>
                    </div>
                @else
                    <div class="table-empty" style="padding:48px 20px;">
                        <i class="fa-solid fa-inbox" style="font-size:2.6rem;"></i>
                        <div style="font-weight:600; margin-top:8px;">Belum ada arsip bulanan.</div>
                        <div style="font-size:0.85rem; color:var(--text-sec); margin-top:6px; max-width:520px; margin-inline:auto;">Arsip akan otomatis tersimpan ketika memasuki bulan baru. Statistik periode terpilih di atas dihitung live.</div>
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
<div class="modal-overlay" id="rekapDetailModalSiswa" style="z-index:1600;">
    <div class="modal modal-lg" style="max-width:560px;">
        <div class="modal-header">
            <h3 id="rekapDetailTitleSiswa" style="font-size:1.02rem;">Detail Absensi</h3>
            <button class="modal-close" onclick="closeRekapDetailSiswa()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="rekapDetailBodySiswa" style="min-height:120px;">
            <div style="display:flex; align-items:center; justify-content:center; padding:30px; color:var(--text-sec); gap:10px;"><div class="spinner"></div> Memuat detail...</div>
        </div>
        <div class="modal-footer">
            <button class="btn-modal-cancel" onclick="closeRekapDetailSiswa()">Tutup</button>
        </div>
    </div>
</div>

<script>
    (function(){
        const jenisEl = document.getElementById('jenisSiswa');
        const fBulanan = document.getElementById('fields-siswa-bulanan');
        const fMingguan = document.getElementById('fields-siswa-mingguan');
        const fPeriode = document.getElementById('fields-siswa-periode');
        function toggle(){
            const v = jenisEl.value;
            if(fBulanan) fBulanan.style.display = v==='bulanan' ? 'grid' : 'none';
            if(fMingguan) fMingguan.style.display = v==='mingguan' ? 'grid' : 'none';
            if(fPeriode) fPeriode.style.display = v==='periode' ? 'grid' : 'none';
            if(fBulanan) fBulanan.querySelectorAll('select,input').forEach(el=> el.disabled = v!=='bulanan');
            if(fMingguan) fMingguan.querySelectorAll('input').forEach(el=> el.disabled = v!=='mingguan');
            if(fPeriode) fPeriode.querySelectorAll('input').forEach(el=> el.disabled = v!=='periode');
        }
        if(jenisEl){ jenisEl.addEventListener('change', toggle); toggle(); }

        // Klik sel kalender siswa
        const modal = document.getElementById('rekapDetailModalSiswa');
        const body = document.getElementById('rekapDetailBodySiswa');
        const title = document.getElementById('rekapDetailTitleSiswa');
        function esc(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
        window.closeRekapDetailSiswa = function(){ if(modal) modal.classList.remove('show'); };
        if(modal){
            modal.addEventListener('click', function(e){ if(e.target===modal) closeRekapDetailSiswa(); });
            document.addEventListener('keydown', function(e){ if(e.key==='Escape' && modal.classList.contains('show')) closeRekapDetailSiswa(); });
        }
        document.querySelectorAll('.rekap-badge-cell.clickable').forEach(function(el){
            el.addEventListener('click', function(){
                const uid = this.getAttribute('data-user-id');
                const tgl = this.getAttribute('data-tanggal');
                if(!uid || !tgl) return;
                title.textContent = tgl;
                body.innerHTML = '<div style="display:flex; align-items:center; justify-content:center; padding:30px; color:var(--text-sec); gap:10px;"><div class="spinner"></div> Memuat...</div>';
                modal.classList.add('show');
                fetch('{{ route('rekap.detail') }}?user_id=' + encodeURIComponent(uid) + '&tanggal=' + encodeURIComponent(tgl), {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
                .then(r=>r.json()).then(data=>{
                    if(data.error){ body.innerHTML = '<div style="padding:18px; text-align:center; color:#991B1B;">'+esc(data.error)+'</div>'; return; }
                    let html = '';
                    html += '<div style="background:#F8FAFC; padding:12px 14px; border-radius:10px; margin-bottom:12px; border:1px solid var(--border);">';
                    html += '<div style="font-size:0.78rem; color:var(--text-sec);">'+esc(data.hari)+'</div>';
                    let badgeBg = '#F1F5F9', badgeColor = '#64748B';
                    if(data.code==='H') { badgeBg='#DCFCE7'; badgeColor='#15803D'; }
                    else if(data.code==='T') { badgeBg='#FEF3C7'; badgeColor='#92400E'; }
                    else if(data.code==='I') { badgeBg='#FFFBEB'; badgeColor='#92400E'; }
                    else if(data.code==='S') { badgeBg='#DBEAFE'; badgeColor='#1E40AF'; }
                    else if(data.code==='A') { badgeBg='#FEE2E2'; badgeColor='#991B1B'; }
                    else if(data.type==='belum_pulang' || data.code==='•') { badgeBg='#FFFBEB'; badgeColor='#B45309'; }
                    html += '<div style="font-weight:700; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">'+esc(data.nama)+' <span style="font-family:monospace; font-weight:400; font-size:0.78rem; color:var(--text-sec);">'+esc(data.user_id)+'</span> <span style="background:'+badgeBg+'; color:'+badgeColor+'; padding:3px 10px; border-radius:999px; font-size:0.78rem;">'+esc(data.code)+' — '+esc(data.status||'')+'</span></div>';
                    html += '<div style="font-size:0.82rem; color:var(--text-sec); margin-top:4px;"><i class="fa-solid fa-calendar"></i> '+esc(data.tanggal)+'</div></div>';
                    if(data.code==='A'){ html += '<div style="padding:14px; background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; color:#991B1B; font-weight:600; text-align:center;"><i class="fa-solid fa-user-xmark"></i> '+esc(data.message)+'</div>'; }
                    else if(data.type==='belum_pulang'){
                        html += '<div style="padding:14px; background:#FFFBEB; border:1px solid #FDE68A; border-radius:10px; color:#92400E; font-weight:600; text-align:center;"><i class="fa-solid fa-clock"></i> '+esc(data.message||'Belum Pulang - batas pulang belum terlewati')+'</div>';
                        if(data.jam_masuk){
                            html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px; margin-top:12px;">';
                            html += '<div style="display:flex; justify-content:space-between; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span style="font-size:0.78rem; color:var(--text-sec);">Jam Masuk</span><span style="font-weight:600;">'+esc(data.jam_masuk||'-')+'</span></div>';
                            html += '<div style="display:flex; justify-content:space-between; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span style="font-size:0.78rem; color:var(--text-sec);">Jam Pulang</span><span style="font-weight:600;">'+esc(data.jam_pulang||'-')+'</span></div>';
                            html += '<div style="display:flex; justify-content:space-between; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span style="font-size:0.78rem; color:var(--text-sec);">Status Lokasi</span><span style="font-weight:600;">'+esc(data.status_lokasi||'-')+'</span></div>';
                            html += '<div style="display:flex; justify-content:space-between; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span style="font-size:0.78rem; color:var(--text-sec);">Jarak / Radius</span><span style="font-weight:600;">'+(data.jarak? esc(data.jarak)+'m':'-')+' / '+(data.radius? esc(data.radius)+'m':'-')+'</span></div>';
                            if(data.keterangan && data.keterangan!=='-') html += '<div style="grid-column:1/-1; display:flex; justify-content:space-between; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span style="font-size:0.78rem; color:var(--text-sec);">Keterangan</span><span style="font-weight:600; text-align:right; word-break:break-word;">'+esc(data.keterangan)+'</span></div>';
                            html += '</div>';
                            if(data.bukti_url){
                                const isPdf = (data.bukti_mime && data.bukti_mime.includes('pdf')) || (data.bukti_name && data.bukti_name.toLowerCase().endsWith('.pdf'));
                                html += '<div style="margin-bottom:10px;"><div style="font-size:0.78rem; font-weight:600; margin-bottom:6px;"><i class="fa-solid fa-paperclip"></i> Bukti Pendukung</div>';
                                if(isPdf) html += '<a href="'+esc(data.bukti_url)+'" target="_blank" class="btn-tool" style="display:inline-flex;"><i class="fa-solid fa-file-pdf"></i> Lihat PDF</a>';
                                else html += '<a href="'+esc(data.bukti_url)+'" target="_blank"><img src="'+esc(data.bukti_url)+'" style="max-width:100%; max-height:180px; border-radius:10px; border:1px solid var(--border);"></a>';
                                html += '</div>';
                            }
                            if(data.foto_url) html += '<div style="margin-bottom:6px;"><div style="font-size:0.78rem; font-weight:600; margin-bottom:6px;"><i class="fa-solid fa-camera"></i> Foto Absen</div><a href="'+esc(data.foto_url)+'" target="_blank"><img src="'+esc(data.foto_url)+'" style="max-width:100%; max-height:180px; border-radius:10px; border:1px solid var(--border);"></a></div>';
                        }
                    }
                    else if(['weekend','future','before','pending'].includes(data.type)){ html += '<div style="padding:14px; background:#F8FAFC; border:1px solid var(--border); border-radius:10px; color:var(--text-sec); text-align:center;">'+esc(data.message||data.status)+'</div>'; }
                    else if(data.jam_masuk){
                        html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">';
                        html += '<div style="display:flex; justify-content:space-between; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span style="font-size:0.78rem; color:var(--text-sec);">Jam Masuk</span><span style="font-weight:600;">'+esc(data.jam_masuk||'-')+'</span></div>';
                        html += '<div style="display:flex; justify-content:space-between; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span style="font-size:0.78rem; color:var(--text-sec);">Jam Pulang</span><span style="font-weight:600;">'+esc(data.jam_pulang||'-')+'</span></div>';
                        html += '<div style="display:flex; justify-content:space-between; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span style="font-size:0.78rem; color:var(--text-sec);">Status Lokasi</span><span style="font-weight:600;">'+esc(data.status_lokasi||'-')+'</span></div>';
                        html += '<div style="display:flex; justify-content:space-between; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span style="font-size:0.78rem; color:var(--text-sec);">Jarak / Radius</span><span style="font-weight:600;">'+(data.jarak? esc(data.jarak)+'m':'-')+' / '+(data.radius? esc(data.radius)+'m':'-')+'</span></div>';
                        if(data.keterangan && data.keterangan!=='-') html += '<div style="grid-column:1/-1; display:flex; justify-content:space-between; padding:9px 12px; border-radius:8px; background:#F8FAFC;"><span style="font-size:0.78rem; color:var(--text-sec);">Keterangan</span><span style="font-weight:600; text-align:right; word-break:break-word;">'+esc(data.keterangan)+'</span></div>';
                        html += '</div>';
                        if(data.bukti_url){
                            const isPdf = (data.bukti_mime && data.bukti_mime.includes('pdf')) || (data.bukti_name && data.bukti_name.toLowerCase().endsWith('.pdf'));
                            html += '<div style="margin-bottom:10px;"><div style="font-size:0.78rem; font-weight:600; margin-bottom:6px;"><i class="fa-solid fa-paperclip"></i> Bukti Pendukung</div>';
                            if(isPdf) html += '<a href="'+esc(data.bukti_url)+'" target="_blank" class="btn-tool" style="display:inline-flex;"><i class="fa-solid fa-file-pdf"></i> Lihat PDF</a>';
                            else html += '<a href="'+esc(data.bukti_url)+'" target="_blank"><img src="'+esc(data.bukti_url)+'" style="max-width:100%; max-height:180px; border-radius:10px; border:1px solid var(--border);"></a>';
                            html += '</div>';
                        }
                        if(data.foto_url) html += '<div style="margin-bottom:6px;"><div style="font-size:0.78rem; font-weight:600; margin-bottom:6px;"><i class="fa-solid fa-camera"></i> Foto Absen</div><a href="'+esc(data.foto_url)+'" target="_blank"><img src="'+esc(data.foto_url)+'" style="max-width:100%; max-height:180px; border-radius:10px; border:1px solid var(--border);"></a></div>';
                    } else html += '<div style="padding:14px; background:#F8FAFC; border:1px solid var(--border); border-radius:10px; text-align:center; color:var(--text-sec);">Tidak ada detail.</div>';
                    body.innerHTML = html;
                }).catch(err=>{ body.innerHTML = '<div style="padding:18px; text-align:center; color:#991B1B;">Gagal memuat detail.</div>'; });
            });
        });
    })();
</script>
<script src="{{ asset('js/dashboard.js') }}"></script>
</body>
</html>
