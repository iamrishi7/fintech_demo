<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Exports\Dashboard\Admin\FundRequestExport;
use App\Exports\Dashboard\User\PayoutExport;
use App\Exports\Dashboard\User\TransactionExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\Fund;
use App\Models\FundTransfer;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\WalletTransfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->transaction_id;
        if (!is_null($search) || !empty($search)) {
            $data = Transaction::where('user_id', $request->user()->id)
                ->whereAny(['reference_id', 'id', 'description'], 'LIKE', "%$search%")
                ->latest('created_at')
                ->orderByDesc('id')
                ->paginate(30)->appends(['from' => $request['from'], 'to' => $request['to'], 'transaction_id' => $request['transaction_id']]);
        } else {
            $data = Transaction::where('user_id', $request->user()->id)
                ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
                ->latest('created_at')
                ->orderByDesc('id')
                ->paginate(30)->appends(['from' => $request['from'], 'to' => $request['to'], 'transaction_id' => $request['transaction_id']]);
        }

        return GeneralResource::collection($data);
    }

    public function dailySales(Request  $request): JsonResource
    {
        $data = Transaction::where('user_id', $request->user()->id)->whereBetween('created_at', [Carbon::createFromDate($request->from ?? today())->startOfDay(),  Carbon::createFromDate($request->to ?? today())->endOfDay()])->get();

        $transaction = $data->groupBy(['user_id', 'service'])->map(function ($item) {
            return $item->map(function ($key) {
                return ['debit_amount' => $key->sum('debit_amount'), 'credit_amount' => $key->sum('credit_amount')];
            });
        });

        return GeneralResource::collection($transaction);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function overview(Request $request)
    {
        $user = $request->user();
        $approved_fund_requests = DB::table('fund_requests')->where(['user_id' => $user->id, 'status' => 'approved'])->sum('amount');
        $pending_fund_requests = DB::table('fund_requests')->where(['user_id' => $user->id, 'status' => 'pending'])->sum('amount');
        $fund_transfers = DB::table('fund_transfers')->where(['user_id' => $user->id, 'activity' => 'transfer'])->sum('amount');
        $payouts = Payout::where(['user_id' => $user->id, 'status' => 'success'])->sum('amount');

        $data = [
            'approved_fund_requests' => $approved_fund_requests,
            'pending_fund_requests' => $pending_fund_requests,
            'total_payouts' => $payouts,
            'fund_transfers'  => $fund_transfers
        ];

        return new GeneralResource($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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

    public function fundRequests(Request $request): JsonResource
    {
        $data = Fund::where('user_id', $request->user()->id)
            ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->latest('created_at')
            ->paginate(30)->appends(['from' => $request['from'], 'to' => $request['to']]);

        return GeneralResource::collection($data);
    }

    public function walletTransfers(Request $request): JsonResource
    {
        $data = WalletTransfer::where('from', $request->user()->id)
            ->with('receiver')
            ->filterByRequest($request)
            ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->latest('wallet_transfers.created_at')
            ->paginate(30)->appends(['from' => $request['from'], 'to' => $request['to'], 'receiver_id' => $request['receiver_id']]);

        return GeneralResource::collection($data);
    }

    public function fundTransfers(Request $request): JsonResource
    {
        $data = FundTransfer::where('user_id', $request->user()->id)
            ->with('admin', function ($q) {
                $q->select(['id', 'name']);
            })
            ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->latest('created_at')
            ->paginate(30)->appends(['from' => $request['from'], 'to' => $request['to']]);

        return GeneralResource::collection($data);
    }

    public function showFundRequests(Request $request, Fund $fund): JsonResource
    {
        $data = $fund->where('user_id', $request->user()->id)
            ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->latest('created_at')
            ->with('reviewer')
            ->paginate(30)->appends(['from' => $request['from'], 'to' => $request['to']]);

        return GeneralResource::collection($data);
    }

    public function export(Request $request)
    {
        $request->validate(['format' => ['required', 'in:xlsx,pdf']]);
        switch ($request['report']) {
            case 'payouts':
                return Excel::download(new PayoutExport($request->from, $request->to), "payouts.{$request->format}");
                break;

            case 'transactions':
                return Excel::download(new TransactionExport($request->from, $request->to), "transactions.{$request->format}");
                break;

            default:
                return Excel::download(new TransactionExport($request->from, $request->to), "transactions.{$request->format}");
                break;
        }
    }
}
