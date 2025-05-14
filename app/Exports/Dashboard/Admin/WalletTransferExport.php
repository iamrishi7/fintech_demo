<?php

namespace App\Exports\Dashboard\Admin;

use App\Models\WalletTransfer;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WalletTransferExport implements FromCollection, WithStyles, WithHeadings, ShouldAutoSize
{

    protected $from;
    protected $to;
    protected $request;

    public function __construct($from, $to, $request)
    {
        $this->from = $from;
        $this->to = $to;
        $this->request = $request;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return WalletTransfer::adminFiterByRequest($this->request)
            ->join('users as sender', 'sender.id', '=', 'wallet_transfers.sender_id')
            ->join('users as receiver', 'receiver.id', '=', 'wallet_transfers.receiver_id')
            ->whereBetween('wallet_transfers.created_at', [$this->request->from ?? Carbon::now()->startOfDay(), $this->request->to ?? Carbon::now()->endOfDay()])
            ->select('wallet_transfers.id', 'wallet_transfers.reference_id', 'sender.name as sender_name', 'receiver.name as receiver_name', 'wallet_transfers.amount', 'wallet_transfers.status', 'wallet_transfers.user_remarks')
            ->get();
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }

    public function headings(): array
    {
        return ["ID", "Transaction ID", "User Name", "Reviewer", "Status", "Bank", "Amount", "Transaction Date", "User Remarks", "Admin Remarks", "Created At", "Upadated At"];
    }
}
