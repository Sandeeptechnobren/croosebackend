<?php
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\OrdiioPaymentsController;
use App\Http\Controllers\OrdiioLicenseController;
use App\Http\Controllers\API\SubscriptionsController;
use Stripe\Stripe;
// use App\Http\Controllers\OrdiioPaymentController;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/payment-success',function(){
    return view('payment.success');
})->name('payment.success');
Route::get('/ordiio_payment_success',function(){
    return view('payments_ordiio.ordiio_success');
});
Route::get('/ordiio_payment_cancel',function(){
    return view('payments_ordiio.ordiio_cancel');
});
Route::get('/ordiio_payment_success', [OrdiioPaymentsController::class, 'paymentSuccess'])
     ->name('ordiio.payment.success.page');

Route::get('/ordiio_payment_cancel', [OrdiioPaymentsController::class, 'paymentCancel'])
     ->name('ordiio.payment.cancel.page');

Route::get('/payment-cancel', [TransactionController::class, 'cancel'])->name('payment.cancel');

Route::get('/payment/order/{uuid}', [TransactionController::class, 'showOrderPaymentOptions'])->name('payment.order.options');
Route::get('/payment/appointment/{uuid}', [TransactionController::class, 'showAppointmentPaymentOptions'])->name('payment.appointment.options');
Route::get('/payment/instance/{uuid}',[TransactionController::class,'showWhapiPaymentOptions'])->name('payment.instance.options');
Route::get('/payment/subscription/{uuid}',[TransactionController::class,'showSubscriptionPaymentOptions'])->name('payment.subscription.options');


Route::post('/ordiio/create-checkout', [OrdiioPaymentsController::class, 'createCheckout'])->name('ordiio.create.checkout');

Route::get('/ordiio/license/payment/success', [OrdiioLicenseController::class, 'paymentSuccess'])
    ->name('license.payment.success.page');

Route::get('/ordiio/license/payment/cancel', [OrdiioLicenseController::class, 'licenseCancelPage'])
    ->name('license.payment.cancel.page');

    Route::get('/subscribe/{uuid}/{phone}', [SubscriptionsController::class, 'showPaymentPage']);
