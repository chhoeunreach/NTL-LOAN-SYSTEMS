<?php

namespace App;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ArrayExport implements FromArray, WithHeadings
{
    public function __construct(protected array $rows)
    {
    }

    public function array(): array
    {
        return array_map(fn ($row) => (array) $row, $this->rows);
    }

    public function headings(): array
    {
        $first = $this->rows[0] ?? [];

        return array_keys((array) $first);
    }
}
