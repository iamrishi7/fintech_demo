<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return GeneralResource::collection(Bank::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'account_number' => ['required', 'digits_between:9,17'],
            'ifsc_code' => ['required', 'string', 'regex:/^[A-Za-z]{4}\d{7}$/'],
            'beneficiary_name' => ['required', 'string'],
            'upi_id' => ['nullable', 'string']
        ]);

        $bank = Bank::create([
            'name' => $request->name,
            'account_number' => $request->account_number,
            'ifsc_code' => $request->ifsc_code,
            'beneficiary_name' => $request->beneficiary_name,
            'status' => $request->status ?? 1,
            'upi_id' => $request->upi_id
        ]);

        return new GeneralResource($bank);
    }

    /**
     * Display the specified resource.
     */
    public function show(Bank $bank)
    {
        return new GeneralResource($bank);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bank $bank)
    {
        $bank->update([
            'name' => $request->name ?? $bank->name,
            'account_number' => $request->account_number ?? $bank->account_number,
            'ifsc_code' => $request->ifsc_code ?? $bank->ifsc_code,
            'beneficiary_name' => $request->beneficiary_name ?? $bank->beneficiary_name,
            'status' => $request->status ?? $bank->status,
            'upi_id' => $request->upi_id ?? $bank->upi_id
        ]);

        return new GeneralResource($bank);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bank $bank)
    {
        $data = $bank->delete();
        return $data;
    }

    public function activeBanks()
    {
        return GeneralResource::collection(Bank::where('status', true)->get());
    }
}
