<?php
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use Stripe\Stripe;
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
Route::get('ordiio_subscriber_success',function(){
    return view('billing.success');
})->name('billing.success');
Route::get('ordiio_subscriber_cancel',function(){
    return view('billing.cancel');
})->name('billing.cancel');
Route::get('/payment-cancel', [TransactionController::class, 'cancel'])->name('payment.cancel');

Route::get('/payment/order/{uuid}', [TransactionController::class, 'showOrderPaymentOptions'])->name('payment.order.options');
Route::get('/payment/appointment/{uuid}', [TransactionController::class, 'showAppointmentPaymentOptions'])->name('payment.appointment.options');
Route::get('/payment/instance/{uuid}',[TransactionController::class,'showWhapiPaymentOptions'])->name('payment.instance.options');
Route::get('/payment/subscription/{uuid}',[TransactionController::class,'showSubscriptionPaymentOptions'])->name('payment.subscription.options');

