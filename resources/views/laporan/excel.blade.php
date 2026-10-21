<table>
    <thead>
        <tr>
            <th colspan="7" style="font-size:14px; font-weight:bold; text-align:left;">Laporan Kegiatan Siswa PKL</th>
        </tr>
        <tr>
            <th colspan="7" style="font-size:10px; color:#6B7280; text-align:left;">Dicetak: {{ now()->format('d-m-Y H:i') }} | Jumlah Data: {{ $items->count() }} | PT Sandya Network</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Nama Siswa</th>
            <th>USERNAME</th>
            <th>Kelas / Jurusan</th>
            <th>Tanggal</th>
            <th>Teks Laporan</th>
            <th>Foto</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($items as $idx => $row)
        @php
            $nama = $row->user?->nama ?? $row->user?->username ?? '-';
            $nis = $row->user?->username ?? '-';
            // cek apakah ada kolom kelas/jurusan, fallback -
            $kelasJurusan = $row->user?->kelas ?? $row->user?->kelas_jurusan ?? $row->user?->jurusan ?? '-';
            if (isset($row->user->nis) || isset($row->user->nisn)) {
                // nis diambil, kelas tetap tampil
            }
            $fotoInfo = !empty($row->foto) ? url(route('laporan.foto', $row->id, false)) : '-';
            // jika ingin path storage sebagai fallback jika foto tidak ada route
            if (!empty($row->foto)) {
                // coba tampilkan URL absolut file
                $fotoInfo = request()->getSchemeAndHttpHost() . route('laporan.foto', $row->id, false);
            }
        @endphp
        <tr>
            <td style="vertical-align:top;">{{ $idx + 1 }}</td>
            <td style="vertical-align:top;">{{ $nama }}</td>
            <td style="vertical-align:top;">{{ $nis }}</td>
            <td style="vertical-align:top;">{{ $kelasJurusan }}</td>
            <td style="vertical-align:top;">{{ $row->tanggal_laporan ? \Carbon\Carbon::parse($row->tanggal_laporan)->format('d-m-Y') : '-' }}</td>
            <td style="vertical-align:top; white-space:normal; word-wrap:break-word;">{{ $row->teks_laporan }}</td>
            <td style="vertical-align:top;">{{ $fotoInfo }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center; color:#9CA3AF;">Tidak ada data laporan.</td>
        </tr>
        @endforelse
    </tbody>
</table>
