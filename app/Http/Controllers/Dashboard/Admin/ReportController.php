<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Exports\Dashboard\Admin\FundRequestExport;
use Carbon\Carbon;
use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\GeneralResource;
use App\Exports\Dashboard\Admin\PayoutExport;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Exports\Dashboard\Admin\TransactionExport;
use App\Exports\Dashboard\Admin\WalletTransferExport;
use App\Models\Fund;
use App\Models\FundTransfer;
use App\Models\User;
use App\Models\WalletTransfer;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $data = Transaction::with(['beneficiary', 'reviewer', 'triggered_by'])
            ->adminFiterByRequest($request)
            ->whereBetween('transactions.created_at', [$request->from, $request->to])
            ->latest('transactions.created_at')
            ->orderByDesc('transactions.id')
            ->paginate(30)->appends(['from' => $request['from'], 'to' => $request['to'], 'account_number' => $request['account_number'], 'user_id' => $request['user_id']]);

        return GeneralResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function overview(Request $request)
    {
        $volume = DB::table('users')->sum('wallet');
        $approved_fund_requests = DB::table('fund_requests')->where('status', 'approved')->sum('amount');
        $pending_fund_requests = DB::table('fund_requests')->where('status', 'pending')->sum('amount');
        $fund_transfers = DB::table('fund_transfers')->where('activity', 'transfer')->sum('amount');
        $payouts = Payout::where('status', 'success')->sum('amount');
        $retailers = User::role('retailer')->count();
        $admins = User::role('admin')->count();

        $data = [
            'volume' => $volume,
            'approved_fund_requests' => $approved_fund_requests,
            'pending_fund_requests' => $pending_fund_requests,
            'total_payouts' => $payouts,
            'retailers' => $retailers,
            'admins' => $admins,
            'fund_transfers'  => $fund_transfers
        ];

        return new GeneralResource($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $data = Transaction::with(['beneficiary', 'reviewer', 'triggered_by'])
            ->whereBetween('created_at', [$request->start, $request->end])
            ->where(function ($q) use ($id) {
                $q->where('user_id', $id)
                    ->orWhere('triggered_by', $id)
                    ->orWhere('updated_by', $id);
            })
            ->paginate(30);

        return GeneralResource::collection($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function dailySales(Request $request): JsonResource
    {
        $transaction = Transaction::dailySales()->whereBetween('transactions.created_at', [Carbon::createFromDate($request->from ?? today())->startOfDay(),  Carbon::createFromDate($request->to ?? today())->endOfDay()])
            ->get()->groupBy('user_id');
        return GeneralResource::collection($transaction);
    }

    public function walletTransferReport(Request $request): JsonResource
    {
        $data = WalletTransfer::adminFiterByRequest($request)
            ->with(['sender', 'receiver'])
            ->whereBetween('wallet_transfers.created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->latest('wallet_transfers.created_at')
            ->paginate(30)->appends(['from' => $request['from'], 'to' => $request['to'], 'sender_id' => $request['sender_id'], 'receiver_id' => $request['receiver_id']]);
        return GeneralResource::collection($data);
    }

    public function fundRequestReport(Request $request): JsonResource
    {
        $data = Fund::adminFiterByRequest($request)->with(['reviewer' => function ($q) {
            $q->select('id', 'name', 'phone_number');
        }, 'user' => function ($q) {
            $q->select('id', 'name', 'phone_number');
        }, 'bank'])->whereBetween('fund_requests.created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->latest('fund_requests.created_at')
            ->paginate(30)->appends(['from' => $request['from'], 'to' => $request['to'], 'transaction_id' => $request['transaction_id'], 'status' => $request['status'], 'user_id' => $request['user_id']]);
        return GeneralResource::collection($data);
    }

    public function payoutReports(Request $request): JsonResource
    {
        $data = Payout::with('user')
            ->adminFilterByRequest($request->toArray())
            ->whereBetween('payouts.created_at', [$request->from ?? Carbon::now()->startOfWeek(), $request->to ?? Carbon::now()->endOfDay()])
            ->latest('payouts.created_at')
            ->paginate(30)->appends(['from' => $request['from'], 'to' => $request['to'], 'account_number' => $request['account_number'], 'utr' => $request['utr'], 'transaction_id' => $request['transaction_id'], 'user_id' => $request['user_id'], 'status' => $request['status']]);
        return GeneralResource::collection($data);
    }

    public function fundTransferReport(Request $request)
    {
        $data = FundTransfer::adminFilterByRequest($request)
            ->with(['user' => function ($q) {
                $q->select('id', 'name', 'phone_number');
            }, 'admin' => function ($q) {
                $q->select('id', 'name', 'phone_number');
            }])->whereBetween('fund_transfers.created_at', [$request->from ?? Carbon::now()->startOfWeek(), $request->to ?? Carbon::now()->endOfDay()])
            ->latest('fund_transfers.created_at')
            ->paginate(30)->appends(['from' => $request['from'], 'to' => $request['to'], 'user_id' => $request['user_id']]);

        return GeneralResource::collection($data);
    }

    public function export(Request $request)
    {
        $request->validate([
            // 'user_id' => ['required', 'exists:users,id'],
            'format' => ['required', 'in:xlsx,pdf']
        ]);
        switch ($request['report']) {
            case 'payouts':
                return Excel::download(new PayoutExport($request->from, $request->to, $request->user_id, $request->status), "payouts.{$request->format}");
                break;

            case 'ledger':
                return Excel::download(new TransactionExport($request->from, $request->to, $request->user_id), "transactions.{$request->format}");
                break;

            case 'fund-requests':
                return Excel::download(new FundRequestExport($request->from, $request->to, $request), "fund_requests.{$request->format}");
                break;

            case 'wallet-transfers':
                return Excel::download(new WalletTransferExport($request->from, $request->to, $request), "fund_requests.{$request->format}");
                break;

            default:
                return Excel::download(new TransactionExport($request->from, $request->to, $request->user_id), "transactions.{$request->format}");
                break;
        }
    }
}
