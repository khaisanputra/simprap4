@php
    $isBulanan = ($jenis ?? 'bulanan') === 'bulanan';
    $dateCols = $tanggalList->count() * 2;
    $totalCols = 3 + $dateCols + 5;
@endphp
<table>
    <thead>
        <tr>
            <th colspan="{{ $totalCols }}" style="font-size:14px; font-weight:bold; text-align:left;">Rekap Absen — {{ $labelPeriode }}</th>
        </tr>
        <tr>
            <th colspan="{{ $totalCols }}" style="font-size:10px; color:#6B7280; text-align:left;">Periode: {{ $labelPeriode }} | Jumlah Siswa: {{ $users->count() }} | Dicetak: {{ now()->format('d-m-Y H:i') }}</th>
        </tr>
        <tr>
            <th colspan="{{ $totalCols }}" style="font-size:10px; color:#6B7280; text-align:left;">Keterangan: H=Hadir, T=Telat, I=Izin/Dispensasi, S=Sakit, A=Alpha, -=Libur, OK=Laporan ada</th>
        </tr>
        @if($isBulanan)
        <tr>
            <th rowspan="2" style="background:#0F4C81; color:#FFFFFF; text-align:center;">No</th>
            <th rowspan="2" style="background:#0F4C81; color:#FFFFFF;">User ID</th>
            <th rowspan="2" style="background:#0F4C81; color:#FFFFFF;">Nama Siswa</th>
            @foreach($tanggalList as $d)
                <th colspan="2" style="background:#EAB308; color:#422006; text-align:center; font-size:9px;">{{ $d->format('d') }}<br>{{ $d->translatedFormat('D') }}</th>
            @endforeach
            <th rowspan="2" style="background:#0A3A63; color:#FFFFFF; text-align:center;">Hadir</th>
            <th rowspan="2" style="background:#0A3A63; color:#FFFFFF; text-align:center;">Izin</th>
            <th rowspan="2" style="background:#0A3A63; color:#FFFFFF; text-align:center;">Sakit</th>
            <th rowspan="2" style="background:#0A3A63; color:#FFFFFF; text-align:center;">Alpha</th>
            <th rowspan="2" style="background:#0A3A63; color:#FFFFFF; text-align:center;">Laporan</th>
        </tr>
        <tr>
            @foreach($tanggalList as $d)
                <th style="background:#0F4C81; color:#FFFFFF; text-align:center; font-size:9px;">Absen</th>
                <th style="background:#1D4ED8; color:#FFFFFF; text-align:center; font-size:9px;">Lap</th>
            @endforeach
        </tr>
        @else
        <tr>
            <th rowspan="2" style="background:#0F4C81; color:#FFFFFF; text-align:center;">No</th>
            <th rowspan="2" style="background:#0F4C81; color:#FFFFFF;">User ID</th>
            <th rowspan="2" style="background:#0F4C81; color:#FFFFFF;">Nama Siswa</th>
            @foreach($tanggalList as $d)
                <th colspan="2" style="background:#0F4C81; color:#FFFFFF; text-align:center; font-size:9px;">{{ $d->format('d') }}<br>{{ $d->translatedFormat('D') }}</th>
            @endforeach
            <th rowspan="2" style="background:#0A3A63; color:#FFFFFF; text-align:center;">Hadir</th>
            <th rowspan="2" style="background:#0A3A63; color:#FFFFFF; text-align:center;">Izin</th>
            <th rowspan="2" style="background:#0A3A63; color:#FFFFFF; text-align:center;">Sakit</th>
            <th rowspan="2" style="background:#0A3A63; color:#FFFFFF; text-align:center;">Alpha</th>
            <th rowspan="2" style="background:#0A3A63; color:#FFFFFF; text-align:center;">Laporan</th>
        </tr>
        <tr>
            @foreach($tanggalList as $d)
                <th style="background:#0F4C81; color:#FFFFFF; text-align:center; font-size:9px;">Absen</th>
                <th style="background:#1D4ED8; color:#FFFFFF; text-align:center; font-size:9px;">Lap</th>
            @endforeach
        </tr>
        @endif
    </thead>
    <tbody>
        @foreach($users as $idx => $u)
            @php
                $uid = $u->id;
                $tot = $totals[$uid] ?? ['hadir'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0,'laporan'=>0];
            @endphp
            <tr>
                <td style="text-align:center;">{{ $idx + 1 }}</td>
                <td>{{ $u->user_id ?? '-' }}</td>
                <td>{{ $u->nama ?? $u->username }}</td>
                @foreach($tanggalList as $d)
                    @php
                        $dStr = $d->toDateString();
                        $cell = $matrix[$uid][$dStr] ?? ['code'=>'-'];
                        $hasLap = $cell['laporan'] ?? false;
                    @endphp
                    <td style="text-align:center;">{{ $cell['code'] }}</td>
                    <td style="text-align:center; font-weight:bold;">{{ $hasLap ? 'OK' : '-' }}</td>
                @endforeach
                <td style="text-align:center; font-weight:bold;">{{ $tot['hadir'] }}</td>
                <td style="text-align:center; font-weight:bold;">{{ $tot['izin'] }}</td>
                <td style="text-align:center; font-weight:bold;">{{ $tot['sakit'] }}</td>
                <td style="text-align:center; font-weight:bold;">{{ $tot['alpha'] }}</td>
                <td style="text-align:center; font-weight:bold;">{{ $tot['laporan'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
