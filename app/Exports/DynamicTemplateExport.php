<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DynamicTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(protected string $modelClass) {}

    public function headings(): array
    {
        return collect($this->modelClass::importColumns())
            ->pluck('label')
            ->values()
            ->toArray();
    }

    public function array(): array
    {
        // No sample rows — just the header row for users to fill in.
        return [];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
