<?php

namespace App\Exports;

use App\Services\GoalsSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** A strategy's goals per person, for analysis in Excel. */
class GoalsSheetExport implements FromArray, WithColumnWidths, WithEvents, WithHeadings, WithStyles
{
    /** @param list<list<string>> $rows */
    public function __construct(private array $rows) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return GoalsSheet::HEADINGS;
    }

    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 28, 'C' => 24, 'D' => 24, 'E' => 40, 'F' => 60, 'G' => 60, 'H' => 60];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '36839B']]],
            'A:H' => ['alignment' => ['wrapText' => true, 'vertical' => 'top']],
        ];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $sheet->freezePane('A2');
            $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        }];
    }
}
