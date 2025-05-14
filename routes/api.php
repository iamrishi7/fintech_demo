<?php

use App\Http\Controllers\Auth\OtpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Services\Bbps\FlowController as BbpsFlowController;
use App\Http\Controllers\Dashboard\User\UserController;
use App\Http\Controllers\Dashboard\Admin\BankController;
use App\Http\Controllers\Dashboard\Admin\PlanController;
use App\Http\Controllers\Dashboard\Admin\AdminController;
use App\Http\Controllers\Dashboard\Admin\ReportController;
use App\Http\Controllers\Dashboard\Admin\WebsiteController;
use App\Http\Controllers\Dashboard\Admin\CommissionController;
use App\Http\Controllers\Dashboard\Admin\CustomizationController;
use App\Http\Controllers\Dashboard\User\FundRequestController;
use App\Http\Controllers\Dashboard\Admin\UserController as AdminUserController;
use App\Http\Controllers\Services\Payout\FlowController as PayoutFlowController;
use App\Http\Controllers\Dashboard\User\ReportController as UserReportController;
use App\Http\Controllers\Dashboard\Admin\FundRequestController as AdminFundRequestController;
use App\Http\Controllers\Dashboard\User\AddressController;
use App\Http\Controllers\Dashboard\User\DocumentController;
use App\Http\Controllers\Dashboard\User\OnboardController;
use App\Http\Controllers\Services\Payout\CallbackController;
use App\Models\Payout;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('services', [WebsiteController::class, 'services']);
Route::get('banks', [BankController::class, 'activeBanks']);
Route::apiResource('cutomizations', CustomizationController::class);

Route::get('verify/{id}', [UserController::class, 'verifyUser'])->middleware('auth:api');
Route::put('credentials', [UserController::class, 'updateCredential'])->middleware('auth:api');

Route::post('otp', [OtpController::class, 'mailOtp']);

/**************** User Routes ****************/
Route::middleware('auth:api')->prefix('user')->group(function () {
    Route::prefix('transaction')->middleware(['freeze', 'pin', 'onboard_active', 'balance', 'throttle:1,0.167'])->group(function () {
        Route::post('payout', [PayoutFlowController::class, 'store']);
        Route::post('bbps', [BbpsFlowController::class, 'store']);
        Route::post('wallet-transfer', [FundRequestController::class, 'walletTransfer']);
    });

    Route::prefix('services')->group(function () {
        Route::prefix('bbps')->controller(BbpsFlowController::class)->group(function () {
            Route::get('categories', 'categories');
            Route::get('operators', 'operators');
        });
    });

    Route::prefix('onboard')->controller(OnboardController::class)->group(function () {
        Route::get('eko', 'ekoOnboard')->middleware('profile');
    });

    Route::prefix('verify')->controller(DocumentController::class)->group(function () {
        Route::put('pan', 'panVerification');
        // Route::get('pan', 'getPanDetails');
    });

    Route::apiResource('fund-requests', FundRequestController::class)->names(['users.fund_requests.index']);
    Route::apiResource('address', AddressController::class);
    Route::get('wallet', [UserController::class, 'wallet']);
    Route::get('permissions', [UserController::class, 'permissions']);
    Route::put('update', [UserController::class, 'updateProfile']);
    Route::post('document', [UserController::class, 'uploadDocument']);

    Route::prefix('report')->group(function () {
        Route::apiResource('ledger', UserReportController::class);
        Route::get('overview', [UserReportController::class, 'overview']);
        Route::get('payout', [PayoutFlowController::class, 'index']);
        Route::get('wallet-transfer', [UserReportController::class, 'walletTransfers']);
        Route::get('fund-transfer', [UserReportController::class, 'fundTransfers']);
        Route::get('daily-sales', [UserReportController::class, 'dailySales']);
        Route::post('export', [UserReportController::class, 'export']);
    });
});

/**************** Admin Routes ****************/
Route::prefix('admin')->middleware(['auth:api', 'role:admin'])->group(function () {
    Route::apiResource('fund-requests', AdminFundRequestController::class);
    Route::post('funds/assign-request', [AdminFundRequestController::class, 'assignRequest']);

    Route::group(['prefix' => 'manage-access'], function () {
        Route::put('update-role', [AdminController::class, 'updateRole']);
        Route::put('sync-user-permissions/{user}', [AdminController::class, 'updateUserPermission']);
        Route::put('sync-role-permissions/{role}', [AdminController::class, 'updateRolePermission']);
        Route::get('role-permissions/{role}', [AdminController::class, 'rolePermissions']);
        Route::get('permissions', [AdminController::class, 'permissions']);
        Route::get('roles', [AdminController::class, 'roles']);
    });

    Route::group(['prefix' => 'controls'], function () {
        Route::put('services/{service}', [WebsiteController::class, 'updateService']);
        Route::post('services', [WebsiteController::class, 'storeService']);
        Route::put('fund-request-limit', [WebsiteController::class, 'addLimit']);
        Route::apiResource('bank', BankController::class);
    });

    Route::group(['prefix' => 'report'], function () {
        Route::apiResource('ledger', ReportController::class);
        Route::get('overview', [ReportController::class, 'overview']);
        Route::get('daily-sales', [ReportController::class, 'dailySales']);
        Route::get('payout', [ReportController::class, 'payoutReports']);
        Route::get('wallet-transfer', [ReportController::class, 'walletTransferReport']);
        Route::get('fund-transfer', [ReportController::class, 'fundTransferReport']);
        Route::get('fund-requests', [ReportController::class, 'fundRequestReport']);
        Route::post('export', [ReportController::class, 'export']);
    });

    Route::group(['prefix' => 'manage-user'], function () {
        Route::apiResource('users', AdminUserController::class);
        Route::put('address/{user_id}', [AdminUserController::class, 'address']);
        Route::get('address/{user_id}', [AdminUserController::class, 'getAddress']);
        Route::post('update-user/{user}', [AdminUserController::class, 'update']);
        Route::put('send-credentials/{user}', [AdminUserController::class, 'sendCredential']);
        Route::put('restore/{id}', [AdminUserController::class, 'restore']);
        Route::get('permissions/{user}', [AdminUserController::class, 'userPermissions']);
        Route::post('document/{user}', [AdminUserController::class, 'uploadDocument']);
    });

    Route::get('document', [AdminUserController::class, 'downloadDocument']);

    Route::apiResource('plans', PlanController::class);

    Route::prefix('commissions')->group(function () {
        Route::get('get-commission/{id}', [CommissionController::class, 'getCommission']);
        Route::post('create-commission', [CommissionController::class, 'createCommission']);
        Route::put('update-commission/{id}', [CommissionController::class, 'updateCommission']);
        Route::delete('delete-commission/{id}', [CommissionController::class, 'deleteCommission']);
    });

    Route::prefix('transactions')->group(function () {
        Route::put('payout/{id}', [PayoutFlowController::class, 'update']);
        Route::post('fund-transfer', [AdminFundRequestController::class, 'fundTransfer'])->middleware(['pin']);
    });
});

Route::prefix('callback/payout')->controller(CallbackController::class)->group(function () {
    Route::post('eko', 'eko');
    Route::post('safexpay', 'safexpay');
    Route::post('groscope', 'groscope');
    Route::post('payninja', 'payninja');
    Route::post('cashfree', 'cashfree');
    Route::post('flipzik', 'flipzik');
    Route::post('runpaisa', 'runpaisa');
});

Route::get('fail-transaction/{transction_id}', function(string $transction_id){
    return Payout::where('reference_id', $transction_id)->update(['status' => 'failed']);
});
