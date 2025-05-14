<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\GeneralResource;
use App\Models\Document;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function wallet(): JsonResource
    {
        $user = auth()->user();
        return new GeneralResource(['wallet' => $user->wallet]);
    }

    public function updateProfile(Request $request): JsonResource
    {
        $user = User::find($request->user()->id);
        $user->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'name' => Str::squish($request->first_name . ' ' . $request->middle_name . ' ' . $request->last_name),
            'phone_number' => $request->phone_number ?? $user->phone_number,
            // 'aadhaar_number' => $request->aadhaar_number ?? $user->aadhaar_number,
            // 'pan_number' => $request->pan_number ?? $user->getOriginal('pan_number'),
            'date_of_birth' => $request->date_of_birth ?? $user->date_of_birth
        ]);

        return new GeneralResource($user);
    }

    public function updateCredential(Request $request): JsonResource
    {
        $request->validate([
            'credential_type' => ['required', 'in:password,pin'],
            'old_credential' => ['required', 'min:4'],
            'new_credential' => ['required', 'min:4', 'confirmed']
        ]);

        $user = User::findOrFail($request->user()->id);
        if (!Hash::check($request->old_credential, $user->{$request->credential_type})) {
            abort(422, "Credentials do not match our records.");
        }

        $user->update([
            $request->credential_type => Hash::make($request->new_credential)
        ]);

        return new GeneralResource($user);
    }

    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document_type' => ['required', 'string', 'max:30'],
            'file' => ['required', 'mimes:jpeg,png,jpg,pdf', 'max:2048']
        ]);

        Document::updateOrInsert(
            [
                'user_id' => $request->user()->id,
                'document_type' => $request->document_type
            ],
            [
                'address' => $request->file('file')->store("users/{$request->document_type}"),
                'updated_at' => now(),
                'created_at' => now()
            ]
        );

        return new GeneralResource($request->user());
    }

    public function permissions(Request $request): JsonResource
    {
        $user = User::findOrFail($request->user()->id);
        return GeneralResource::collection($user->getAllPermissions());
    }

    public function verifyUser(string $id)
    {
        $user = User::where('phone_number', $id)->select('id', 'name', 'phone_number')->first();
        if (!$user) {
            abort(404, "User not found.");
        }
        return new GeneralResource($user);
    }
}
