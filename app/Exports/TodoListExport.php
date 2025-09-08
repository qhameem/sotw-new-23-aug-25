<?php

namespace App\Exports;

use App\Models\TodoList;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TodoListExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $todoList;

    public function __construct(TodoList $todoList)
    {
        $this->todoList = $todoList;
    }

    public function collection()
    {
        return $this->todoList->items;
    }

    public function headings(): array
    {
        return [
            'Task',
            'Priority',
            'Status',
            'Deadline',
        ];
    }

    public function map($item): array
    {
        $colors = [
            'red' => '1',
            'yellow' => '2',
            'purple' => '3',
            'blue' => '4',
            'indigo' => '5',
            'green' => '6',
            'pink' => '7',
            'gray' => '8'
        ];

        return [
            $item->title,
            $colors[$item->color] ?? $item->color,
            $item->completed ? 'Completed' : 'Pending',
            $item->deadline,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $colorMap = [
            'red'    => 'FFFFCDCD', // Light Red
            'yellow' => 'FFFFFF00', // Light Yellow
            'purple' => 'FFE0B6FF', // Light Purple
            'blue'   => 'FFBDE6F0', // Light Blue
            'indigo' => 'FFC5CAE9', // Light Indigo
            'green'  => 'FFC8E6C9', // Light Green
            'pink'   => 'FFF8BBD0', // Light Pink
            'gray'   => 'FFF5F5F5', // Light Gray
        ];

        foreach ($this->collection() as $index => $item) {
            $rowNumber = $index + 2; // +2 because Excel is 1-indexed and we have a header row
            if (isset($colorMap[$item->color])) {
                $sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->getFill()
                      ->setFillType(Fill::FILL_SOLID)
                      ->getStartColor()->setARGB($colorMap[$item->color]);
            }
        }

        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
        ];
    }
}