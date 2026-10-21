<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataAbsenExport implements FromView, WithColumnWidths, WithStyles, WithTitle
{
    public $items;
    public $filters;
    public $lokasiKantor;

    public function __construct($items, $filters = [], $lokasiKantor = [])
    {
        $this->items = $items;
        $this->filters = $filters;
        $this->lokasiKantor = $lokasiKantor;
    }

    public function view(): View
    {
        // periode text for header
        $periode = $this->periodeText($this->filters);
        return view('data-absen-masuk.excel', [
            'items' => $this->items,
            'filters' => $this->filters,
            'lokasiKantor' => $this->lokasiKantor,
            'periode' => $periode,
        ]);
    }

    protected function periodeText(array $filters): string
    {
        $dari = $filters['tanggal_dari'] ?? null;
        $sampai = $filters['tanggal_sampai'] ?? null;
        if ($dari && $sampai) {
            if ($dari === $sampai) {
                return \Carbon\Carbon::parse($dari)->translatedFormat('d F Y');
            }
            return \Carbon\Carbon::parse($dari)->translatedFormat('d F Y') . ' - ' . \Carbon\Carbon::parse($sampai)->translatedFormat('d F Y');
        }
        if ($dari) {
            return 'Mulai ' . \Carbon\Carbon::parse($dari)->translatedFormat('d F Y');
        }
        if ($sampai) {
            return 'Sampai ' . \Carbon\Carbon::parse($sampai)->translatedFormat('d F Y');
        }
        return 'Semua Periode';
    }

    public function title(): string
    {
        return 'Data Absen';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 18,  // User ID
            'C' => 24,  // Nama Siswa
            'D' => 14,  // Tanggal
            'E' => 12,  // Jam Masuk
            'F' => 12,  // Jam Pulang
            'G' => 15,  // Jenis Kehadiran
            'H' => 24,  // Keterangan
            'I' => 14,  // Bukti
            'J' => 12,  // Jarak
            'K' => 12,  // Radius
            'L' => 12,  // Akurasi
            'M' => 16,  // Status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6B7280']]],
            4 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0F4C81']]],
        ];
    }
}
