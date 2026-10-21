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
        @foreach ($activities as $idx => $a)
        <tr>
            <td>{{ $idx + 1 }}</td>
            <td>{{ $a->user?->user_id ?? '-' }}</td>
            <td>{{ $a->nama }}</td>
            <td>{{ $a->status_kehadiran ?? $a->status }}</td>
            <td>{{ $a->keterangan ?? '-' }}</td>
            <td>{{ !empty($a->bukti_file) ? 'Ada Bukti' : 'Tidak Ada Bukti' }}</td>
            <td>{{ $a->tgl_masuk ? \Carbon\Carbon::parse($a->tgl_masuk)->format('d-m-Y') : '-' }}</td>
            <td>{{ $a->jam_masuk ?? '-' }}</td>
            @php $labelExemptExcel = $a->status_kehadiran ?? $a->status; $isExemptExcel = in_array($labelExemptExcel, ['Izin','Dispensasi','Sakit'], true); @endphp
            <td>{{ $a->tgl_pulang ? \Carbon\Carbon::parse($a->tgl_pulang)->format('d-m-Y') : ($isExemptExcel ? 'Tidak Diperlukan' : '-') }}</td>
            <td>{{ $a->jam_pulang ?? ($isExemptExcel ? 'Selesai' : '-') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
