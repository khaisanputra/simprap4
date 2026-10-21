<table>
    <thead>
        <tr>
            <th colspan="13" style="font-size:14px; font-weight:bold; text-align:left;">Data Absen</th>
        </tr>
        <tr>
            @php $statusExcelLabels = ['masuk'=>'Masuk','telat'=>'Telat','sakit'=>'Sakit','dispen'=>'Dispen','izin'=>'Izin']; $statusExcel = $filters['status'] ?? null; @endphp
            <th colspan="13" style="font-size:10px; color:#6B7280; text-align:left;">Periode: {{ $periode }}@if(!empty($filters['cari'])) | Pencarian: "{{ $filters['cari'] }}"@endif @if(!empty($statusExcel)) | Status: {{ $statusExcelLabels[$statusExcel] ?? ucfirst($statusExcel) }}@endif</th>
        </tr>
        <tr>
            <th colspan="13" style="font-size:10px; color:#6B7280; text-align:left;">Dicetak: {{ now()->format('d-m-Y H:i') }} | Jumlah Data: {{ $items->count() }} | {{ $lokasiKantor['nama'] ?? 'PT Sandya Network' }}</th>
        </tr>
        <tr>
            <th>No</th>
            <th>User ID</th>
            <th>Nama Siswa</th>
            <th>Tanggal</th>
            <th>Jam Masuk</th>
            <th>Jam Pulang</th>
            <th>Jenis Kehadiran</th>
            <th>Keterangan</th>
            <th>Bukti</th>
            <th>Jarak</th>
            <th>Radius</th>
            <th>Akurasi</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($items as $idx => $row)
        <tr>
            <td>{{ $idx + 1 }}</td>
            <td>{{ $row->user?->user_id ?? '-' }}</td>
            <td>{{ $row->nama_tampil }}</td>
            <td>{{ $row->tgl_masuk ? \Carbon\Carbon::parse($row->tgl_masuk)->format('d-m-Y') : '-' }}</td>
            <td>{{ $row->jam_masuk ?? '-' }}</td>
            <td>@php $isExempt = in_array($row->status_kehadiran_label ?? $row->status_kehadiran ?? $row->status, ['Izin','Dispensasi','Sakit'], true); @endphp {{ $row->jam_pulang ?? ($isExempt ? 'Tidak Diperlukan' : '-') }}</td>
            <td>{{ $row->status_kehadiran_label ?? $row->status_kehadiran ?? $row->status }}</td>
            <td>{{ $row->keterangan ?? '-' }}</td>
            <td>{{ !empty($row->bukti_file) ? 'Ada Bukti' : 'Tidak Ada' }}</td>
            <td>{{ $row->jarak_tampil !== null ? number_format($row->jarak_tampil, 2, ',', '.') . ' m' : '-' }}</td>
            <td>{{ $row->radius_tampil !== null ? number_format($row->radius_tampil, 0, ',', '.') . ' m' : '-' }}</td>
            <td>{{ $row->accuracy !== null ? '±' . number_format($row->accuracy, 1, ',', '.') . ' m' : '-' }}</td>
            <td>@php $labelExcel = $row->status_kehadiran_label ?? $row->status_kehadiran ?? $row->status ?? '-'; @endphp {{ $labelExcel === 'Dispensasi' ? 'Dispen' : $labelExcel }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="13" style="text-align:center; color:#9CA3AF;">Tidak ada data Absen yang cocok.</td>
        </tr>
        @endforelse
    </tbody>
</table>
