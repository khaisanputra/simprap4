<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Absensi PKL</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1F2937; padding: 24px; }
        .print-header { display: flex; align-items: center; gap: 14px; border-bottom: 3px solid #0F4C81; padding-bottom: 12px; margin-bottom: 16px; }
        .logo-box { width: 56px; height: 56px; border-radius: 0; background: transparent; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; overflow: visible; padding: 0; border: none; }
        .print-header h1 { font-size: 18px; color: #0F4C81; }
        .print-header p { font-size: 12px; color: #6B7280; }
        .meta { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 14px; }
        .meta b { color: #0F4C81; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #0F4C81; color: #fff; font-size: 11px; padding: 9px 8px; text-align: left; }
        tbody td { padding: 8px; border: 1px solid #E5E7EB; font-size: 12px; }
        tbody tr:nth-child(even) { background: #F8FAFC; }
        .no-print { text-align: center; margin-bottom: 16px; }
        .no-print button { padding: 10px 22px; border: none; background: #0F4C81; color: #fff; border-radius: 8px; cursor: pointer; font-size: 14px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()"><b>Cetak / Print</b></button>
    </div>

    <div class="print-header">
        <div class="logo-box" style="background:transparent; padding:0; border:none; box-shadow:none;">
            <img src="{{ asset('images/logo-sandya.png') }}" alt="Sandya Absensi PKL" style="width:100%; height:auto; max-height:56px; object-fit:contain; display:block;">
        </div>
        <div>
            <h1>{{ $lokasi['nama'] ?? 'PT Sandya Network' }}</h1>
            <p>Sistem Absensi PKL</p>
        </div>
    </div>

<div class="meta">
        <div>
            <b>Laporan Aktivitas Absensi Hari Ini</b><br>
            Periode: {{ now()->translatedFormat('d F Y') }}
            @if (!empty($filter['cari']))
                <br>Pencarian: "{{ $filter['cari'] }}"
            @endif
        </div>
        <div>
            Dicetak: {{ now()->format('d-m-Y H:i') }}<br>
            Jumlah Data: <b>{{ $activities->count() }}</b>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>User ID</th>
                <th>Nama</th>
                <th>Status</th>
                <th>Keterangan</th>
                <th>Bukti</th>
                <th>Tanggal Masuk</th>
                <th>Jam Masuk</th>
                <th>Tanggal Pulang</th>
                <th>Jam Pulang</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($activities as $idx => $a)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $a->user?->user_id ?? '-' }}</td>
                <td>{{ $a->nama }}</td>
                <td>{{ $a->status_kehadiran ?? $a->status }}</td>
                <td>{{ $a->keterangan ?? '-' }}</td>
                <td>{{ !empty($a->bukti_file) ? 'Ada Bukti' : 'Tidak Ada Bukti' }}</td>
                <td>{{ $a->tgl_masuk ? \Carbon\Carbon::parse($a->tgl_masuk)->format('d-m-Y') : '-' }}</td>
                <td>{{ $a->jam_masuk ?? '-' }}</td>
                @php $labelExemptPrint = $a->status_kehadiran ?? $a->status; $isExemptPrint = in_array($labelExemptPrint, ['Izin','Dispensasi','Sakit'], true); @endphp
                <td>{{ $a->tgl_pulang ? \Carbon\Carbon::parse($a->tgl_pulang)->format('d-m-Y') : ($isExemptPrint ? 'Tidak Diperlukan' : '-') }}</td>
                <td>{{ $a->jam_pulang ?? ($isExemptPrint ? 'Selesai' : '-') }}</td>
            </tr>
            @empty
            <tr><td colspan="10" style="text-align:center;color:#9CA3AF;padding:20px;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>
