<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanExport implements FromView, WithColumnWidths, WithStyles, WithTitle
{
    public $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function view(): View
    {
        return view('laporan.excel', [
            'items' => $this->items,
        ]);
    }

    public function title(): string
    {
        return 'Laporan Siswa';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 24,  // Nama
            'C' => 16,  // NIS/Username fallback
            'D' => 18,  // Kelas/Jurusan
            'E' => 16,  // Tanggal
            'F' => 55,  // Teks Laporan
            'G' => 32,  // Foto (path/url)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Wrap text untuk kolom teks laporan
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle('A1:G' . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('F1:F' . $highestRow)->getAlignment()->setWrapText(true);
            // Auto row height handled by Excel; alignment top
            for ($r = 2; $r <= $highestRow; $r++) {
                $sheet->getRowDimension($r)->setRowHeight(-1);
            }
        }
        return [
            3 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0F4C81']]],
        ];
    }
}
