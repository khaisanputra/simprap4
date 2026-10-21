<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Absen Print — {{ $labelPeriode }}</title>
<style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Poppins',Arial,sans-serif; color:#1F2937; padding:18px; font-size:10px;}
    h1{font-size:16px; color:#0F4C81; margin-bottom:4px;}
    .sub{font-size:11px; color:#6B7280; margin-bottom:14px;}
    table{width:100%; border-collapse:collapse;}
    th,td{border:1px solid #E5E7EB; padding:4px 5px; text-align:center; font-size:9px;}
    th{background:#0F4C81; color:#fff; font-weight:600;}
    th.tanggal{font-size:8px; line-height:1.1;}
    td.name{ text-align:left; font-weight:500; }
    .small{font-size:8px; color:#6B7280;}
    .badge{display:inline-block; min-width:14px; height:14px; line-height:14px; border-radius:4px; font-weight:700; font-size:8px;}
    .h{background:#DCFCE7; color:#15803D;}
    .t{background:#FEF3C7; color:#92400E;}
    .i{background:#FFFBEB; color:#92400E;}
    .s{background:#DBEAFE; color:#1E40AF;}
    .a{background:#FEE2E2; color:#991B1B;}
    .dash{background:#F1F5F9; color:#64748B;}
    .ok{background:#DBEAFE; color:#1E40AF;}
    th.date-group{background:#EAB308; color:#422006;}
    th.sub-absen{background:#0F4C81; color:#fff; font-size:7px;}
    th.sub-lap{background:#1D4ED8; color:#fff; font-size:7px;}
    .legend{display:flex; gap:12px; flex-wrap:wrap; margin-top:10px; font-size:8px; color:#6B7280;}
    .legend span{display:inline-flex; gap:4px; align-items:center;}
    thead{display:table-header-group;}
    tr{page-break-inside:avoid;}
    @media print{ body{padding:10px;} @page{size:landscape; margin:10mm;} }
</style>
</head>
<body>
<h1>Rekap Absen — {{ $labelPeriode }}</h1>
<p class="sub">
    @if($jenis==='mingguan') Rekap Mingguan
    @elseif($jenis==='periode') Rekap Periode
    @else Rekap Bulanan
    @endif
    • {{ $start->translatedFormat('d F Y') }} - {{ $end->translatedFormat('d F Y') }}
    • Dicetak: {{ now()->translatedFormat('d F Y H:i') }} • {{ $users->count() }} siswa
</p>
<table>
    <thead>
        @if(($jenis ?? 'bulanan') === 'bulanan')
        <tr>
            <th rowspan="2" style="width:26px;">No</th>
            <th rowspan="2" style="width:80px;">User ID</th>
            <th rowspan="2" style="width:130px;">Nama</th>
            @foreach($tanggalList as $d)
                <th colspan="2" class="tanggal date-group">{{ $d->format('d') }}<br><span style="font-weight:400; font-size:7px;">{{ $d->translatedFormat('D') }}</span></th>
            @endforeach
            <th rowspan="2" style="background:#0A3A63;">Hadir</th>
            <th rowspan="2" style="background:#0A3A63;">Izin</th>
            <th rowspan="2" style="background:#0A3A63;">Sakit</th>
            <th rowspan="2" style="background:#0A3A63;">Alpha</th>
            <th rowspan="2" style="background:#0A3A63;">Laporan</th>
        </tr>
        <tr>
            @foreach($tanggalList as $d)
                <th class="sub-absen">Absen</th>
                <th class="sub-lap">Lap</th>
            @endforeach
        </tr>
        @else
        <tr>
            <th rowspan="2" style="width:26px;">No</th>
            <th rowspan="2" style="width:80px;">User ID</th>
            <th rowspan="2" style="width:130px;">Nama</th>
            @foreach($tanggalList as $d)
                <th colspan="2" class="tanggal">{{ $d->format('d') }}<br><span style="font-weight:400; font-size:7px;">{{ $d->translatedFormat('D') }}</span></th>
            @endforeach
            <th rowspan="2" style="background:#0A3A63;">Hadir</th>
            <th rowspan="2" style="background:#0A3A63;">Izin</th>
            <th rowspan="2" style="background:#0A3A63;">Sakit</th>
            <th rowspan="2" style="background:#0A3A63;">Alpha</th>
            <th rowspan="2" style="background:#0A3A63;">Laporan</th>
        </tr>
        <tr>
            @foreach($tanggalList as $d)
                <th class="sub-absen">Absen</th>
                <th class="sub-lap">Lap</th>
            @endforeach
        </tr>
        @endif
    </thead>
    <tbody>
        @foreach($users as $idx => $u)
            @php $uid=$u->id; $tot=$totals[$uid] ?? ['hadir'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0,'laporan'=>0]; @endphp
            <tr>
                <td>{{ $idx+1 }}</td>
                <td style="font-family:monospace; font-size:8px;">{{ $u->user_id ?? '-' }}</td>
                <td class="name">{{ $u->nama ?? $u->username }}</td>
                @foreach($tanggalList as $d)
                    @php $cell=$matrix[$uid][$d->toDateString()] ?? ['code'=>'-']; $c=strtolower($cell['code']); $hasLap=$cell['laporan'] ?? false; @endphp
                    <td><span class="badge {{ $c==='h'?'h':($c==='t'?'t':($c==='i'?'i':($c==='s'?'s':($c==='a'?'a':'dash')))) }}">{{ $cell['code'] }}</span></td>
                    <td><span class="badge {{ $hasLap ? 'ok' : 'dash' }}">{{ $hasLap ? 'OK' : '-' }}</span></td>
                @endforeach
                <td style="font-weight:700;">{{ $tot['hadir'] }}</td>
                <td style="font-weight:700;">{{ $tot['izin'] }}</td>
                <td style="font-weight:700;">{{ $tot['sakit'] }}</td>
                <td style="font-weight:700;">{{ $tot['alpha'] }}</td>
                <td style="font-weight:700;">{{ $tot['laporan'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="legend">
    <span><span class="badge h">H</span> Hadir</span>
    <span><span class="badge t">T</span> Telat</span>
    <span><span class="badge i">I</span> Izin/Dispensasi</span>
    <span><span class="badge s">S</span> Sakit</span>
    <span><span class="badge a">A</span> Alpha</span>
    <span><span class="badge dash">-</span> Libur</span>
    <span><span class="badge ok">OK</span> Laporan ada</span>
</div>
<script>window.print();</script>
</body>
</html>
