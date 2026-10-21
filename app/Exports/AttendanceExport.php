<?php

namespace App\Exports;

use App\Models\Attendance;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromView, WithColumnWidths, WithStyles, WithTitle
{
    /** @var \Illuminate\Database\Eloquent\Collection|\App\Models\Attendance[] */
    public $activities;

    public function __construct($activities)
    {
        $this->activities = $activities;
    }

    public function view(): View
    {
        return view('exports.excel', [
            'activities' => $this->activities,
        ]);
    }

    public function title(): string
    {
        return 'Laporan Absensi';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,  // No
            'B' => 18, // User ID
            'C' => 28, // Nama
            'D' => 18, // Status
            'E' => 28, // Keterangan
            'F' => 16, // Bukti
            'G' => 16, // Tgl Masuk
            'H' => 14, // Jam Masuk
            'I' => 16, // Tgl Pulang
            'J' => 14, // Jam Pulang
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
