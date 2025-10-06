<?php

namespace App\Exports;

use App\Models\TodoList;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TodoListExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $todoList;
    private $rowNumber = 1;

    public function __construct(TodoList $todoList)
    {
        $this->todoList = $todoList;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->todoList->items;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Task Name',
            'Priority',
            'Deadline',
        ];
    }

    /**
     * @param mixed $item
     *
     * @return array
     */
    public function map($item): array
    {
        $priorityNames = [
            'rose' => 'Priority 1',
            'orange' => 'Priority 2',
            'yellow' => 'Priority 3',
            'green' => 'Priority 4',
            'gray' => 'Priority 5',
        ];

        return [
            $item->title,
            $priorityNames[$item->color] ?? $item->color,
            $item->deadline,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $colorMap = [
            'rose' => 'FFC7CE',   // Light Red
            'orange' => 'FFEB9C', // Light Yellow
            'yellow' => 'FFFF00', // Yellow
            'green' => 'C6EFCE',  // Light Green
            'gray' => 'F2F2F2',   // Light Gray
        ];

        foreach ($this->todoList->items as $index => $item) {
            $rowNumber = $index + 2; // +2 because Excel is 1-based and we have a header row
            $color = $item->color ?? 'gray';

            if (isset($colorMap[$color])) {
                $sheet->getStyle("A{$rowNumber}:C{$rowNumber}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($colorMap[$color]);
            }
        }

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}