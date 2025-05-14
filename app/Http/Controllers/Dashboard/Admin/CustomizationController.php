<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\Customization;
use Illuminate\Http\Request;

class CustomizationController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin', ['except' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return GeneralResource::collection(Customization::first());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $input = $request->validate([
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
            'auth_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
            'comapny_name' => ['required', 'string', 'max:40'],
            'portal_name' => ['nullable', 'string', 'max:40'],
            'logo_config' => ['required', 'string', 'max:60'],
            'receipt_footer' => ['required', 'boolean'],
            'theme' => ['nullable', 'string', 'max:20'],
            'receipt_layout' => ['required', 'string', 'max:20']
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logo');
            $input['logo'] = $path;
        }

        if ($request->hasFile('auth_image')) {
            $path = $request->file('auth_image')->store('auth_image');
            $input['auth_image'] = $path;
        }

        return new GeneralResource(Customization::create($input));
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
    public function update(Request $request, Customization $customization)
    {

        if ($request->hasFile('logo')) {
            $logo_path = $request->file('logo')->store('logo');
        } else {
            $logo_path = $customization->logo;
        }

        if ($request->hasFile('auth_image')) {
            $auth_image_path = $request->file('auth_image')->store('auth_image');
        } else {
            $auth_image_path = $customization->auth_iamge;
        }

        $customization->logo = $logo_path;
        $customization->auth_iamge = $auth_image_path;
        $customization->comapny_name = $request->comapny_name ?? $customization->comapny_name;
        $customization->portal_name = $request->portal_name ?? $customization->portal_name;
        $customization->logo_config = $request->logo_config ?? $customization->logo_config;
        $customization->receipt_footer = $request->receipt_footer ?? $customization->receipt_footer;
        $customization->theme = $request->theme ?? $customization->theme;
        $customization->receipt_layout = $request->receipt_layout ?? $customization->receipt_layout;
        $customization->save();

        return new GeneralResource($customization);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
