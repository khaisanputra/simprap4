<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Absen - Cetak</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1F2937; padding: 24px; background:#fff; }
        .print-header { display:flex; flex-direction:row; align-items:center; gap:18px; background:#fff; border-bottom:1px solid #0F4C81; padding-bottom:10px; margin-bottom:16px; }
        .logo-box { width:60px; height:auto; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .logo-box img { width:58px; height:auto; max-height:56px; object-fit:contain; display:block; }
        .print-header .header-text { display:flex; flex-direction:column; gap:4px; text-align:left; }
        .print-header h1 { font-size:18px; font-weight:800; color:#0F4C81; letter-spacing:0.02em; line-height:1.1; }
        .print-header p { font-size:11px; color:#6B7280; }
        .meta { display: flex; justify-content: space-between; font-size: 11px; color:#374151; margin-bottom: 14px; gap:16px; flex-wrap:wrap; }
        .meta b { color: #0F4C81; }
        .meta-box { line-height:1.6; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead th { background: #0F4C81; color: #fff; font-size: 10px; text-transform: uppercase; padding: 8px 6px; text-align: left; white-space: nowrap; }
        tbody td { padding: 7px 6px; border: 1px solid #E5E7EB; vertical-align: middle; }
        tbody tr:nth-child(even) { background: #F8FAFC; }
        .foto-print { width: 36px; height: 36px; object-fit: cover; border-radius: 6px; border:1px solid #E5E7EB; display:block; }
        .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:10px; font-weight:600; white-space:nowrap; }
        .badge-masuk{ background:#DCFCE7; color:#15803D; } .badge-telat{ background:#FEF3C7; color:#B45309; border:1px solid #FDE68A; } .badge-tidak{ background:#FEE2E2; color:#991B1B; border:1px solid #FECACA; } .badge-tanpa{ background:#F1F5F9; color:#64748B; } .badge-izin{ background:#FFFBEB; color:#B45309; border:1px solid #FDE68A; } .badge-dispensasi{ background:#EFF6FF; color:#1D4ED8; border:1px solid #BFDBFE; } .badge-sakit{ background:#FEF2F2; color:#B91C1C; border:1px solid #FECACA; }
        .no-print { text-align: center; margin-bottom: 16px; }
        .no-print button { padding: 10px 22px; border: none; background: #0F4C81; color: #fff; border-radius: 8px; cursor: pointer; font-size: 14px; margin:0 6px; }
        .no-print a { display:inline-block; padding:10px 18px; border:1px solid #E5E7EB; border-radius:8px; color:#374151; text-decoration:none; font-size:14px; margin:0 6px; background:#fff; }
        .table-wrap { overflow-x:auto; }
        .empty-row td { text-align:center; color:#9CA3AF; padding:20px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4 landscape; margin: 12mm; }
            table { font-size:10px; }
            .print-header, .logo-box, .logo-box img { visibility: visible !important; opacity: 1 !important; }
            .print-header { flex-direction:row !important; align-items:center !important; text-align:left !important; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()"><b>🖨 Cetak</b></button>
        <a href="{{ route('data-absen-masuk.index', request()->query()) }}">← Kembali ke Data Absen</a>
    </div>

    <div class="print-header">
        <div class="logo-box">
            <img src="{{ asset('images/logo-sandya.png') }}" alt="Logo Sandya" style="object-fit:contain;">
        </div>
        <div class="header-text">
            <h1>DATA ABSEN</h1>
            <p>{{ $lokasiKantor['nama'] ?? 'PT Sandya Network' }} — {{ $lokasiKantor['alamat'] ?? 'Sistem Absensi PKL' }}</p>
        </div>
    </div>

    <div class="meta">
        <div class="meta-box">
            <b>Periode:</b> {{ $periode }}<br>
            @if(!empty($filters['cari'])) <b>Pencarian:</b> "{{ $filters['cari'] }}"<br> @endif
            @php
                $statusPrintLabels = ['masuk'=>'Masuk','telat'=>'Telat','sakit'=>'Sakit','dispen'=>'Dispen','izin'=>'Izin'];
                $statusPrint = $filters['status'] ?? null;
            @endphp
            @if(!empty($statusPrint)) <b>Status:</b> {{ $statusPrintLabels[$statusPrint] ?? ucfirst($statusPrint) }}<br> @endif
            @if(!empty($filters['tanggal_dari']) || !empty($filters['tanggal_sampai'])) <b>Filter Tanggal:</b> {{ $filters['tanggal_dari'] ?? '-' }} s/d {{ $filters['tanggal_sampai'] ?? '-' }}<br> @endif
        </div>
        <div class="meta-box" style="text-align:right;">
            Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB<br>
            Jumlah Data: <b>{{ $items->count() }}</b><br>
            <span style="font-size:10px; color:#6B7280;">{{ now()->format('d-m-Y H:i') }}</span>
        </div>
    </div>

    <div class="table-wrap">
    <table>
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
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $idx => $row)
            @php
                $isExempt = in_array($row->status_kehadiran_label, ['Izin','Dispensasi','Sakit'], true);
                $badgeMap = ['Masuk'=>'badge-masuk','Telat'=>'badge-telat','Tidak Masuk'=>'badge-tidak','Izin'=>'badge-izin','Dispensasi'=>'badge-dispensasi','Sakit'=>'badge-sakit','Pulang'=>'badge-masuk'];
                $badgeCls = $badgeMap[$row->status_kehadiran_label] ?? 'badge-masuk';
                $displayStatusPrint = $row->status_kehadiran_label === 'Dispensasi' ? 'Dispen' : $row->status_kehadiran_label;
                $statusClass = $badgeMap[$row->status_kehadiran_label] ?? 'badge-masuk';
                $statusText = $displayStatusPrint;
                $userIdPrint = $row->user?->user_id ?? ($row->user_id ? \App\Models\User::where('id', $row->user_id)->value('user_id') : null);
            @endphp
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td style="font-family:monospace; white-space:nowrap; font-size:10px;">{{ $userIdPrint ?? '-' }}</td>
                <td style="font-weight:500; white-space:nowrap;">{{ $row->nama_tampil }}</td>
                <td style="white-space:nowrap;">{{ $row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->format('d-m-Y') : '-' }}</td>
                <td style="white-space:nowrap;">{{ $row->jam_masuk ?? '-' }}</td>
                <td style="white-space:nowrap;">@if($row->jam_pulang) {{ $row->jam_pulang }} @elseif($isExempt) <span style="color:#10B981;font-weight:600;font-size:10px;">Tidak Diperlukan</span> @else - @endif</td>
                <td><span class="badge {{ $badgeCls }}">{{ $row->status_kehadiran_label }}</span></td>
                <td style="white-space:nowrap;">@if($row->bukti_file) <span style="color:#15803D;font-weight:600;">Ada</span> @else <span style="color:#9CA3AF;">Tidak Ada</span> @endif</td>
                <td>
                    @if($row->foto)
                        <img src="{{ route('foto.lihat', $row->id) }}" alt="Foto" class="foto-print" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <span style="display:none; font-size:10px; color:#9CA3AF;">Tidak ada</span>
                    @else
                        <span style="font-size:10px; color:#9CA3AF;">-</span>
                    @endif
                </td>
                <td><span class="badge {{ $statusClass }}">{{ $statusText }}</span></td>
            </tr>
            @empty
            <tr class="empty-row"><td colspan="10">Tidak ada data Absen yang cocok untuk filter yang dipilih.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <div style="margin-top:18px; font-size:10px; color:#6B7280; text-align:right;">
        Diterbitkan oleh Sistem Absensi PKL {{ $lokasiKantor['nama'] ?? 'PT Sandya Network' }} — {{ now()->translatedFormat('l, d F Y H:i') }} WIB
    </div>

    <script>
        window.addEventListener('load', function() {
            // auto print only if not already printed via button
            // window.print();
        });
    </script>
</body>
</html>
