<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpensesExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $branchId;
    protected $dateFrom;
    protected $dateTo;
    protected $category;

    public function __construct($branchId, $dateFrom = null, $dateTo = null, $category = null)
    {
        $this->branchId = $branchId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->category = $category;
    }

    public function query()
    {
        $query = Expense::with('user')->where('branch_id', $this->branchId);

        if ($this->dateFrom) $query->whereDate('expense_date', '>=', $this->dateFrom);
        if ($this->dateTo)   $query->whereDate('expense_date', '<=', $this->dateTo);
        if ($this->category) {
            if ($this->category === 'uncategorized') {
                $query->whereNull('category');
            } else {
                $query->where('category', $this->category);
            }
        }

        return $query->orderBy('expense_date');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Title',
            'Category',
            'Amount (₵)',
            'Date',
            'User',
            'Notes',
            'Receipt URL'
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->id,
            $expense->title,
            $expense->category ?? 'Uncategorized',
            number_format($expense->amount, 2),
            $expense->expense_date->format('Y-m-d'),
            $expense->user->name,
            $expense->notes ?? '',
            $expense->receipt_url ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}