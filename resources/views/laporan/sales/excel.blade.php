<table>
    <thead>
        <tr>
            <th colspan="9" style="font-size:14px; font-weight:bold; text-align:center; vertical-align:middle;">Laporan Sales Siswa PKL</th>
        </tr>
        <tr>
            <th colspan="9" style="font-size:9px; color:#6B7280; text-align:center; vertical-align:middle;">Dicetak: {{ now()->format('d-m-Y H:i') }} | Jumlah Data: {{ $items->count() }} | PT Sandya Network</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Collector</th>
            <th>Nama Customer</th>
            <th>No Telp</th>
            <th>Alamat Customer</th>
            <th>Provider yg Digunakan</th>
            <th>Kelebihan</th>
            <th>Kekurangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($items as $idx => $row)
        @php
            $collectorName = $row->user?->nama ?? $row->user?->username ?? '-';
        @endphp
        <tr>
            <td>{{ $idx + 1 }}</td>
            <td>{{ $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') : '-' }}</td>
            <td>{{ $collectorName }}</td>
            <td>{{ $row->nama_customer }}</td>
            <td data-type="s">{{ $row->no_telp ?? '-' }}</td>
            <td>{{ $row->alamat_customer }}</td>
            <td>{{ $row->provider }}</td>
            <td>{{ $row->kelebihan ?? '-' }}</td>
            <td>{{ $row->kekurangan ?? '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align:center; color:#9CA3AF;">Tidak ada data laporan Sales.</td>
        </tr>
        @endforelse
    </tbody>
</table>
