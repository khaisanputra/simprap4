<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapCalendarExport implements FromView, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    public $users;
    public $tanggalList;
    public $matrix;
    public $totals;
    public $labelPeriode;
    public $jenis;

    public function __construct($users, $tanggalList, $matrix, $totals, $labelPeriode, $jenis = 'bulanan')
    {
        $this->users = $users;
        $this->tanggalList = $tanggalList;
        $this->matrix = $matrix;
        $this->totals = $totals;
        $this->labelPeriode = $labelPeriode;
        $this->jenis = $jenis ?? 'bulanan';
    }

    public function isBulanan(): bool
    {
        return ($this->jenis ?? 'bulanan') === 'bulanan';
    }

    /**
     * Jumlah kolom tanggal pada sheet: semua jenis rekap memakai 2 subkolom
     * (Absen + Laporan) per tanggal, konsisten dengan tampilan halaman rekap.
     */
    public function dateColumnCount(): int
    {
        return $this->tanggalList->count() * 2;
    }

    public function view(): View
    {
        return view('rekap.excel-calendar', [
            'users' => $this->users,
            'tanggalList' => $this->tanggalList,
            'matrix' => $this->matrix,
            'totals' => $this->totals,
            'labelPeriode' => $this->labelPeriode,
            'jenis' => $this->jenis,
        ]);
    }

    public function title(): string
    {
        return 'Rekap Kalender';
    }

    public function columnWidths(): array
    {
        $widths = [];
        // A No
        $widths['A'] = 6;
        // B User ID — lebar agar G1 - INT0030 tidak terpotong
        $widths['B'] = 20;
        // C Nama Siswa — lebar agar nama panjang tidak terpotong
        $widths['C'] = 32;

        $n = $this->tanggalList->count();
        $dateCols = $this->dateColumnCount();

        // Kolom tanggal D... (dinamis; bulanan = 2 subkolom Absen+Laporan per tanggal)
        for ($i = 0; $i < $dateCols; $i++) {
            $colIndex = 4 + $i; // D = 4
            $col = Coordinate::stringFromColumnIndex($colIndex);
            $widths[$col] = 7;
        }

        // Kolom rekap 5 terakhir: Hadir, Izin, Sakit, Alpha, Laporan
        $startRekap = 4 + $dateCols;
        $rekapWidths = [10, 10, 10, 10, 12];
        foreach ($rekapWidths as $idx => $w) {
            $colIndex = $startRekap + $idx;
            $col = Coordinate::stringFromColumnIndex($colIndex);
            $widths[$col] = $w;
        }

        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        $header = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0F4C81']]];
        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            2 => ['font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6B7280']]],
            4 => $header,
        ];
        // Semua jenis rekap memakai 2 baris header tabel (grup tanggal + subkolom Absen/Laporan)
        $styles[5] = $header;
        return $styles;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $n = $this->tanggalList->count();
                $dateCols = $this->dateColumnCount();
                $totalCols = 3 + $dateCols + 5;
                $lastCol = Coordinate::stringFromColumnIndex($totalCols);
                // Baris header tabel: 4-5 (semua jenis memakai subkolom Absen/Lap per tanggal)
                $headerRow = 5;
                $firstDataRow = $headerRow + 1;
                $lastRow = $headerRow + $this->users->count();

                // Freeze pane: No, User ID, Nama Siswa tetap terlihat saat scroll ke kanan
                // Baris judul + header tetap saat scroll ke bawah
                $sheet->freezePane('D' . $firstDataRow);

                // Tinggi baris header
                $sheet->getRowDimension(4)->setRowHeight(28);
                $sheet->getRowDimension(5)->setRowHeight(18);
                for ($r = $firstDataRow; $r <= $lastRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

                // Alignment & vertical center untuk seluruh tabel + wrap text selektif
                $fullRange = 'A4:' . $lastCol . $lastRow;
                $sheet->getStyle($fullRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // No → center
                if ($lastRow >= $firstDataRow) {
                    $sheet->getStyle('A4:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                // User ID → left
                if ($lastRow >= $firstDataRow) {
                    $sheet->getStyle('B4:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
                // Nama Siswa → left + wrap
                if ($lastRow >= $firstDataRow) {
                    $sheet->getStyle('C4:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle('C4:C' . $lastRow)->getAlignment()->setWrapText(true);
                }
                // Tanggal columns → center + wrap (untuk 01 + hari)
                if ($dateCols > 0) {
                    $firstDateCol = Coordinate::stringFromColumnIndex(4);
                    $lastDateCol = Coordinate::stringFromColumnIndex(3 + $dateCols);
                    $sheet->getStyle($firstDateCol . '4:' . $lastDateCol . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle($firstDateCol . '4:' . $lastDateCol . $lastRow)->getAlignment()->setWrapText(true);
                }
                // Rekap columns → center, tidak wrap
                $firstRekapColIndex = 4 + $dateCols;
                $lastRekapColIndex = $totalCols;
                if ($firstRekapColIndex <= $lastRekapColIndex) {
                    $firstRekapCol = Coordinate::stringFromColumnIndex($firstRekapColIndex);
                    $lastRekapCol = Coordinate::stringFromColumnIndex($lastRekapColIndex);
                    $sheet->getStyle($firstRekapCol . '4:' . $lastRekapCol . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle($firstRekapCol . '4:' . $lastRekapCol . $lastRow)->getAlignment()->setWrapText(false);
                }

                // Header row 4 semua center & vertical center
                $sheet->getStyle('A4:' . $lastCol . '4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A4:' . $lastCol . '4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A4:' . $lastCol . '4')->getAlignment()->setWrapText(true);

                // Warna fill untuk 5 kolom rekap agar beda (Hadir,Izin,Sakit,Alpha,Laporan tetap #0A3A63)
                // Pertahankan biru utama #0F4C81 untuk No/UserID/Nama/tanggal, rekap #0A3A63
                if ($firstRekapColIndex <= $lastRekapColIndex) {
                    $firstRekapCol = Coordinate::stringFromColumnIndex($firstRekapColIndex);
                    $lastRekapCol = Coordinate::stringFromColumnIndex($lastRekapColIndex);
                    $sheet->getStyle($firstRekapCol . '4:' . $lastRekapCol . '4')->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '0A3A63'],
                        ],
                    ]);
                }

                // Border tipis pada area tabel
                $sheet->getStyle($fullRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D9E2EC'],
                        ],
                    ],
                ]);

                // Auto filter dimatikan (tidak ada dropdown) — biarkan header clean

                // Print setup tipis agar rapi (opsional, tidak ubah data)
                $sheet->getSheetView()->setZoomScale(95);
            },
        ];
    }
}
