<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return GeneralResource::collection(Plan::with('user')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:plans,name'],
            'default' => ['required', 'boolean']
        ]);

        $plan = Plan::create([
            'name' => $request->name,
            'user_id' => $request->user()->id,
            'default' => $request->default
        ]);

        return new GeneralResource($plan);
    }

    /**
     * Display the specified resource.
     */
    public function show(Plan $plan)
    {
        $data = $plan->with(['payouts', 'user'])->get();
        return new GeneralResource($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Plan $plan)
    {
        Plan::where('default', 1)->update(['default' => 0]);
        $plan->update([
            'name' => $request->name ?? $plan->name,
            'default' => $request->default ?? $plan->default
        ]);

        return new GeneralResource($plan);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plan $plan)
    {
        $plan->delete();
        return response()->noContent();
    }
}
