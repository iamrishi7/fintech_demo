<?php

namespace App\Exports\Dashboard\User;

use Carbon\Carbon;
use App\Models\Payout;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayoutExport implements FromCollection, WithStyles, WithHeadings, ShouldAutoSize
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
        return Payout::where('user_id', auth()->user()->id)
            ->whereBetween('created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
            ->get(['id', 'account_number', 'ifsc_code', 'beneficiary_name', 'reference_id', 'status', 'amount', 'created_at', 'updated_at']);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }

    public function headings(): array
    {
        return ["ID", "Account Number", "IFSC", "Beneficiary Name", "Reference ID", "Status", "Amount", "Created At", "Updated At"];
    }
}
