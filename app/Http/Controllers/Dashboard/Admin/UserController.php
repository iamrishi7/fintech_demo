<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\GeneralResource;
use App\Mail\SendPassword;
use App\Models\Address;
use App\Models\Document;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return GeneralResource::collection(User::role($request->role)->with(['plan' => function ($q) {
            $q->select(['id', 'name']);
        }, 'documents'])->withTrashed()->paginate(30)->appends($request->all()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users'],
            'first_name' => ['required', 'string', 'max:20'],
            'middle_name' => ['nullable', 'string', 'max:20'],
            'last_name' => ['nullable', 'string', 'max:20'],
            'phone_number' => ['nullable', 'digits:10'],
            'role'  => ['required', 'exists:roles,name'],
            'pan_number' => ['nullable', 'regex:/^([A-Z]){5}([0-9]){4}([A-Z]){1}?$/'],
            'aadhaar_number' => ['nullable', 'digits:12']
        ]);

        $password = Str::random(8);
        $pin = rand(1001, 9999);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($password),
            'pin' => Hash::make($pin),
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'name' => Str::squish($request->first_name . ' ' . $request->middle_name . ' ' . $request->last_name),
            'capped_balance' => $request->minimum_balance ?? 0,
            'pan_number' => $request->pan_number,
            'aadhaar_number' => $request->aadhaar_number
        ])->assignRole($request->role);

        Mail::to($request->email)
            ->send(new SendPassword($password, 'password', $pin));

        return new GeneralResource($user);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['documents', 'roles' =>  function ($role) {
            $role->select('name', 'id');
        }, 'permissions' => function ($permission) {
            $permission->select('id', 'name');
        }])->findOrFail($id);
        return new GeneralResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        Log::channel('update')->info($request->user()->id, $request->all());
        $user->update([
            'first_name' => $request->first_name ?? $user->first_name,
            'middle_name' => $request->middle_name ?? $user->middle_name,
            'last_name' => $request->last_name ?? $user->last_name,
            'name' => Str::squish($request->first_name ?? $user->first_name . ' ' . $request->middle_name ?? $user->middle_name . ' ' . $request->last_name ?? $user->last_name),
            'phone_number' => $request->phone_number ?? $user->phone_number,
            'email' => $request->email ?? $user->email,
            'active' => $request->active ?? $user->active,
            'admin_remarks' => $request->admin_remarks ?? $user->admin_remarks,
            'plan_id' => $request->plan_id ?? $user->plan_id,
            'capped_balance' => $request->capped_balance ?? $user->capped_balance,
            'date_of_birth' => $request->date_of_birth ?? $user->date_of_birth,
            'pan_number' => $request->pan_number ?? $user->getRawOriginal('pan_number')
        ]);

        $user->assignRole($request->role);

        return new GeneralResource($user);
    }

    public function uploadDocument(Request $request, User $user)
    {
        $request->validate([
            'document_type' => ['required', 'string', 'max:30'],
            'file' => ['required', 'mimes:jpeg,png,jpg,pdf', 'max:2048']
        ]);

        Document::updateOrInsert(
            [
                'user_id' => $user->id,
                'document_type' => $request->document_type
            ],
            [
                'address' => $request->file('file')->store("users/{$request->document_type}"),
                'updated_at' => now(),
                'created_at' => now()
            ]
        );

        return new GeneralResource($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }

    public function userPermissions(User $user)
    {
        return GeneralResource::collection($user->getAllPermissions());
    }

    public function restore(string $id)
    {
        User::withTrashed()->findOrFail($id)->restore();
        return response()->noContent();
    }

    public function sendCredential(Request $request, User $user): JsonResource
    {
        $request->validate([
            'channel' => ['required', 'in:email,sms'],
            'credential_type' => ['required', 'in:password,pin']
        ]);

        if ($request->channel == 'email') {
            return $this->emailCredentials($request, $user);
        } else {
            return $this->smsCredentials($request, $user);
        }
    }

    public function emailCredentials(Request $request, User $user): JsonResource
    {
        if ($request->credential_type == 'password') {
            $password = Str::random(8);
        } else {
            $password = rand(1001, 9999);
        }
        $user->update([
            $request->credential_type => Hash::make($password)
        ]);

        Mail::to($user->email)
            ->send(new SendPassword($password, $request->credential_type, "####"));

        return new GeneralResource($user);
    }

    public function smsCredentials(Request $request, User $user): JsonResource
    {
        $password = Str::random(8);
        $user->update([
            $request->credential_type => Hash::make($password)
        ]);

        return new GeneralResource($user);
    }

    public function address(Request $request, string $user_id)
    {
        $data = $request->validate([
            'street' => ['required', 'string'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string'],
            'pincode' => ['required', 'digits_between:6,8'],
            'shop_name' => ['required', 'string']
        ]);
        $data['user_id'] = User::findOrFail($user_id)->id;

        Address::updateOrInsert(
            ['user_id' => $data['user_id']],
            $data
        );

        return $data;
    }

    public function getAddress(Request $request, string $user_id)
    {
        $data = Address::where('user_id', $user_id)->get();

        return new GeneralResource($data);
    }

    public function downloadDocument(Request $request)
    {
        return Storage::download($request->path);
    }
}
