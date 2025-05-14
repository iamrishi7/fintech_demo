<?php

namespace App\Exports\Dashboard\User;

use Carbon\Carbon;
use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements FromCollection, WithStyles, WithHeadings, ShouldAutoSize
{
    protected $from;
    protected $to;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Transaction::where('user_id', auth()->user()->id)
            ->whereBetween('created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
            ->get(['id', 'reference_id', 'service', 'credit_amount',  'debit_amount', 'gst', 'opening_balance', 'closing_balance', 'created_at', 'updated_at']);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }

    public function headings(): array
    {
        return ["ID", "Reference ID", "Service", "Credit Amount", "Debit Amount", "GST (Incl.)", "Opening Balance", "Closing Balance", "Created At", "Updated At"];
    }
}
