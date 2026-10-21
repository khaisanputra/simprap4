<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class LaporanSalesExport implements FromView, WithColumnWidths, WithTitle, WithEvents, WithColumnFormatting
{
    public $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function view(): View
    {
        return view('laporan.sales.excel', [
            'items' => $this->items,
        ]);
    }

    public function title(): string
    {
        return 'Laporan Sales';
    }

    public function columnWidths(): array
    {
        // Proporsional & compact - sesuai spec: No & Tanggal kecil, sisanya lebih besar
        return [
            'A' => 6,   // No
            'B' => 14,  // Tanggal
            'C' => 20,  // Collector
            'D' => 22,  // Nama Customer
            'E' => 16,  // No Telp
            'F' => 32,  // Alamat Customer (paling lebar)
            'G' => 18,  // Provider yg Digunakan
            'H' => 22,  // Kelebihan
            'I' => 22,  // Kekurangan
        ];
    }

    public function columnFormats(): array
    {
        // Kolom No Telp sebagai TEXT agar leading 0 tidak hilang
        return [
            'E' => '@',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet */
                $sheet = method_exists($event->sheet, 'getDelegate')
                    ? $event->sheet->getDelegate()
                    : $event->sheet;

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                if ($highestRow < 1) {
                    return;
                }

                // Default font - clean & readable
                $sheet->getParent()->getDefaultStyle()->getFont()
                    ->setName('Calibri')
                    ->setSize(10)
                    ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1F2937'));
                $sheet->getParent()->getDefaultStyle()->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // -------------------------------------------------
                // JUDUL - baris 1: Laporan Sales Siswa PKL
                // Simple, bold, sedikit lebih besar, rata tengah, tanpa banner
                // -------------------------------------------------
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('1F2937');
                $sheet->getStyle('A1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(false);
                $sheet->getRowDimension(1)->setRowHeight(22);
                // Pastikan merge sudah ada dari HTML (colspan 9) - tidak perlu merge ulang
                $sheet->getStyle('A1:I1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A1:I1')->getFill()->setFillType(Fill::FILL_NONE);

                // -------------------------------------------------
                // SUBTITLE - baris 2: Dicetak: ... | Jumlah Data: ... | PT Sandya Network
                // -------------------------------------------------
                if ($highestRow >= 2) {
                    $sheet->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB('6B7280');
                    $sheet->getStyle('A2')->getFont()->setBold(false)->setItalic(false);
                    $sheet->getStyle('A2:I2')->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getRowDimension(2)->setRowHeight(14);
                    $sheet->getStyle('A2:I2')->getFill()->setFillType(Fill::FILL_NONE);
                }

                // -------------------------------------------------
                // HEADER TABEL - baris 3
                // Satu warna solid sederhana, font putih bold, rata tengah, border tipis
                // -------------------------------------------------
                if ($highestRow >= 3) {
                    $headerRange = 'A3:I3';
                    $sheet->getStyle($headerRange)->getFont()
                        ->setBold(true)
                        ->setSize(10)
                        ->getColor()->setRGB('FFFFFF');
                    $sheet->getStyle($headerRange)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('334155'); // slate-700 - solid, profesional
                    $sheet->getStyle($headerRange)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);
                    $sheet->getRowDimension(3)->setRowHeight(20);

                    // Border tipis header - abu-abu muda konsisten
                    $sheet->getStyle($headerRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E5E7EB'],
                            ],
                        ],
                    ]);
                }

                // -------------------------------------------------
                // DATA - mulai baris 4
                // Background putih dominan, alternating row SANGAT subtle, border tipis konsisten
                // -------------------------------------------------
                if ($highestRow >= 4) {
                    $dataRange = 'A4:I' . $highestRow;

                    // Base style untuk seluruh data
                    $sheet->getStyle($dataRange)->getFont()->setSize(9)->getColor()->setRGB('1F2937');
                    $sheet->getStyle($dataRange)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFFFFF');
                    $sheet->getStyle($dataRange)->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    // Border tipis abu-abu muda konsisten untuk data
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E5E7EB'],
                            ],
                        ],
                    ]);

                    // Alternating row SANGAT tipis/subtle - hanya F9FAFB
                    // Revisi: jarak vertikal sedikit lebih lega (18pt base, compact profesional)
                    // Wrap menyesuaikan otomatis, vertical center, tanpa baris kosong
                    $wrapWidths = ['C' => 20, 'D' => 22, 'F' => 32, 'G' => 18, 'H' => 22, 'I' => 22];
                    $baseHeight = 18;
                    $lineHeight = 12;
                    for ($r = 4; $r <= $highestRow; $r++) {
                        if ($r % 2 === 0) {
                            $sheet->getStyle('A' . $r . ':I' . $r)->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('F8FAFC');
                        }
                        // Hitung tinggi baris berdasarkan isi kolom wrap agar teks tetap terbaca
                        $maxLines = 1;
                        foreach ($wrapWidths as $col => $width) {
                            $val = (string) $sheet->getCell($col . $r)->getValue();
                            $val = trim($val);
                            if ($val === '' || $val === '-') {
                                continue;
                            }
                            $len = mb_strlen($val);
                            $lines = (int) ceil($len / $width);
                            if ($lines < 1) $lines = 1;
                            $nlCount = substr_count($val, "\n") + 1;
                            if ($nlCount > $lines) $lines = $nlCount;
                            if ($lines > $maxLines) $maxLines = $lines;
                        }
                        $rowHeight = $baseHeight;
                        if ($maxLines > 1) {
                            $rowHeight = $baseHeight + ($maxLines - 1) * $lineHeight;
                            if ($rowHeight > 60) $rowHeight = 60; // batasi agar tetap compact untuk print
                        }
                        $sheet->getRowDimension($r)->setRowHeight($rowHeight);
                    }

                    // Wrap text hanya untuk kolom yang membutuhkan
                    $sheet->getStyle('C4:C' . $highestRow)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('D4:D' . $highestRow)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('F4:F' . $highestRow)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('G4:G' . $highestRow)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('H4:H' . $highestRow)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('I4:I' . $highestRow)->getAlignment()->setWrapText(true);
                    // Kolom pendek tidak wrap agar tetap compact
                    $sheet->getStyle('A4:A' . $highestRow)->getAlignment()->setWrapText(false);
                    $sheet->getStyle('B4:B' . $highestRow)->getAlignment()->setWrapText(false);
                    $sheet->getStyle('E4:E' . $highestRow)->getAlignment()->setWrapText(false);

                    // Alignment per kolom
                    $sheet->getStyle('A4:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // No
                    $sheet->getStyle('B4:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tanggal
                    $sheet->getStyle('C4:C' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);   // Collector
                    $sheet->getStyle('D4:D' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);   // Nama Customer
                    $sheet->getStyle('E4:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // No Telp
                    $sheet->getStyle('F4:F' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);   // Alamat
                    $sheet->getStyle('G4:G' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);   // Provider
                    $sheet->getStyle('H4:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);   // Kelebihan
                    $sheet->getStyle('I4:I' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);   // Kekurangan

                    // Kolom No Telp - pastikan format TEXT dan nilai string
                    $sheet->getStyle('E4:E' . $highestRow)->getNumberFormat()->setFormatCode('@');
                    // Re-set sebagai string explicit untuk mempertahankan leading 0 jika masih numerik
                    // Gunakan data dari collection untuk restore jika perlu, tapi minimal set explicit string
                    for ($r = 4; $r <= $highestRow; $r++) {
                        $cell = $sheet->getCell('E' . $r);
                        $val = $cell->getValue();
                        if ($val !== null && $val !== '' && $val !== '-') {
                            $str = (string) $val;
                            // Jika nilai numerik dan tidak mengandung leading 0 padahal original ada 0,
                            // kita coba restore dari items collection (index = row - 4)
                            $idx = $r - 4;
                            if (isset($this->items[$idx])) {
                                $orig = $this->items[$idx]->no_telp;
                                if ($orig !== null && $orig !== '' && $orig !== '-') {
                                    $str = (string) $orig;
                                }
                            }
                            // Set explicit string agar Excel tidak hilangkan 0
                            $cell->setValueExplicit($str, DataType::TYPE_STRING);
                        }
                    }
                }

                // -------------------------------------------------
                // Handle empty data - baris 4 colspan 9 "Tidak ada data..."
                // -------------------------------------------------
                if ($highestRow === 4) {
                    $val = $sheet->getCell('A4')->getValue();
                    if ($val && str_contains((string) $val, 'Tidak ada data')) {
                        $sheet->getStyle('A4:I4')->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                            ->setVertical(Alignment::VERTICAL_CENTER);
                        $sheet->getStyle('A4:I4')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF9CA3AF'))->setItalic(true);
                        $sheet->getStyle('A4:I4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
                    }
                }

                // -------------------------------------------------
                // AUTO FILTER DINONAKTIFKAN - header tanpa dropdown/filter (revisi: hilangkan AutoFilter)
                // -------------------------------------------------
                $sheet->removeAutoFilter();

                // -------------------------------------------------
                // FREEZE PANES - header tetap terlihat saat scroll
                // -------------------------------------------------
                $sheet->freezePane('A4');

                // -------------------------------------------------
                // PRINT / PAGE SETUP - Landscape A4 Fit to width 1 page
                // -------------------------------------------------
                $pageSetup = $sheet->getPageSetup();
                $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
                $pageSetup->setFitToWidth(1);
                $pageSetup->setFitToHeight(0);
                $pageSetup->setFitToPage(true);
                $pageSetup->setRowsToRepeatAtTopByStartAndEnd(3, 3);
                $pageSetup->setPrintArea('A1:I' . $highestRow);
                $pageSetup->setHorizontalCentered(false);
                $pageSetup->setVerticalCentered(false);

                $sheet->getPageMargins()->setTop(0.4);
                $sheet->getPageMargins()->setBottom(0.4);
                $sheet->getPageMargins()->setLeft(0.35);
                $sheet->getPageMargins()->setRight(0.35);
                $sheet->getPageMargins()->setHeader(0.2);
                $sheet->getPageMargins()->setFooter(0.2);

                $sheet->setPrintGridlines(false);
                // Gridlines di view tetap subtle karena border sudah ada

                // -------------------------------------------------
                // Lebar print: pastikan tidak terlalu kecil
                // -------------------------------------------------
                $sheet->getSheetView()->setZoomScale(100);
            },
        ];
    }
}
