<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\SourceAudioApiController;
use App\Http\Controllers\PayStackController;
use App\Http\Controllers\OrdiioController;
use App\Http\Controllers\OrdiioFavouritesController;
use App\Http\Controllers\Ordiio_settings_controller;
use App\Http\Controllers\OrdiioCartController;
use App\Http\Controllers\OrdiioPlaylistsController;
use App\Http\Controllers\BusinessCategoryController;
use App\Http\Controllers\WhapiController;
use App\Http\Controllers\OrdiioApiController\OrdiioFilterController;
use App\Http\Controllers\paymentController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\DelaydogController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\BroadcastController;
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/clients', [AuthController::class, 'index'])->name('index');
    Route::get('/getUserData',[OrdiioController::class,'getUserData']);
    Route::post('/send_otp', [AuthController::class, 'sendOtpEmail']);
    Route::post('/reset_password',[AuthController::class,'reset_password_viasecurity']);
    Route::post('/ordiio/forgot-password',[AuthController::class,'forgot_password_ordiio']);
    Route::post('/ordiio/reset-password',[AuthController::class,'reset_password_ordiio']);
    Route::post('/verify-reset-password', [AuthController::class, 'verifyOtpAndResetPassword']);
    Route::post('/find_account/{email}',[AuthController::class,'find_account']);
    Route::middleware('auth:sanctum')->group(function () 
    {
        Route::post('/account_profile',[ClientsController::class,'account_profile']);
        Route::post('/update_password',[ClientsController::class,'update_password']);
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/services/show', [ServicesController::class, 'showById']);
        Route::put('/services/{id}', [ServicesController::class, 'update']);
        Route::post('/services', [ServicesController::class, 'store']);
        Route::get('/services', [ServicesController::class, 'get_services']);
        Route::post('/services/bulkupload',[ServicesController::class, 'addbulkservices']);
        Route::delete('/services/{id}', [ServicesController::class, 'destroy']);
        Route::get('/getServicesBySpace',[ServicesController::class,'getServicesBySpace']);

        //products API
        Route::get('/products', [ProductsController::class, 'get_products']);
        Route::put('/products/{id}', [ProductsController::class, 'update']);
        Route::delete('/products/{id}', [ProductsController::class, 'destroy']);
        Route::post('/products', [ProductsController::class, 'store']);
        Route::post('/products/bulkupload', [ProductsController::class, 'addBulkProducts']);
        Route::get('/getProductBySpace', [ProductsController::class, 'getProductBySpace']);

        //customer API
        Route::post('/customer/register', [CustomerController::class, 'store']);
        Route::get('/getCustomer',[CustomerController::class,'getCustomer']);
        Route::get('/getCustomerByPhone',[CustomerController::class,'getCustomerByPhone']);

        Route::get('/customer_statistics',[CustomerController::class,'customerStatistics']);
        
        //Appointment
        Route::get('/appointments', [AppointmentController::class, 'show']);
        Route::get('/appointment_statistics',[AppointmentController::class,'appointment_statistics']);
        Route::post('/appointments_status_update', [AppointmentController::class, 'updateStatus']);
        Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
        
        //order
        Route::get('/order_statistics',[OrderController::class,'order_statistics']);
        Route::get('/orders',[OrderController::class,'show']);
        Route::post('/orders_status_update', [OrderController::class, 'updateOrderStatus']);
        Route::post('/createmanualorder',[OrderController::class,'createmanualorder']);

        //  SpaceController
        Route::post('/create_space', [SpaceController::class, 'store']);
        Route::get('/space',[SpaceController::class,'show']);
        Route::post('/update_space',[SpaceController::class,'update_space']);
        Route::post('/check-user-space', [SpaceController::class, 'checkUserHasSpace']);
        Route::post('/checkspaceIQincresed',[SpaceController::class,'checkspaceIQincresed']);
        Route::post('/space_iq',[SpaceController::class,'space_iq']);
        Route::get('/get_space_list', [SpaceController::class, 'get_spaces_list']);
        Route::get('/get_space_prompt',[SpaceController::class,'get_space_prompt']);
        Route::post('/update_space_prompt',[SpaceController::class,'update_space_prompt']);
        Route::get('/space_chat_stats',[SpaceController::class,'space_chat_stats']);
        Route::get('/space_chat_list',[SpaceController::class,'space_chat_list']);
        Route::post('/space_activation_charge',[SpaceController::class,'space_activation_charge']);

        //categories 
        Route::get('/get_categories',[CategoriesController::class,'get_categories']);
        Route::post('/get_template/{type}',[ClientsController::class,'downloadTemplate']);

        //conversation_controller
        Route::get('/get_conversations',[ConversationController::class,'get_conversations']);
        Route::get('/total_chats',[ConversationController::class,'total_chats']);
        //whapi Automation
        Route::post('/whapi/instance', [WhapiController::class, 'createInstance']);
        Route::post('/whapi/instancenew', [WhapiController::class, 'createInstance1']);
        Route::get('/whapi/instance/qr', [WhapiController::class, 'fetchQrCode'])->name('whapi.qr');
        Route::get('/whapi/instance_activation_status',[WhapiController::class,'instance_activation_status']);
        //Payment Controller
        Route::post('/payment_details',[paymentController::class,'payment_details']);
        //Subscription Controller
        Route::post('/create_subscription',[SubscriptionController::class,'createsubscription']);
        Route::get('/subscription_list',[SubscriptionController::class,'getSubscriptionlist']);
        Route::post('/check_name',[SubscriptionController::class,'checkName']);
        Route::get('/subscribers_list',[SubscriptionController::class,'subscribers_list']);
        Route::get('/subscriber_statistics',[SubscriptionController::class,'subscriber_statistics']);

        //ordiio_controller 
        Route::post('/ordiio_logout',[OrdiioController::class,'logout']);
        Route::get('/get_license_category',[OrdiioController::class,'get_license_category']); 
        Route::post('/purchase_ordiio_license',[OrdiioController::class,'purchase_ordiio_license']);
        Route::post('/purchase_ordiio_subscription',[OrdiioController::class,'purchase_ordiio_subscription']);
        Route::get('/get-signed-url/{trackId}', [SourceAudioApiController::class, 'getSignedUrl']);
        Route::post('/add_to_favourites',[OrdiioFavouritesController::class,'addToFavourites']);
        Route::get('/deleteFavourites/{id}',[OrdiioFavouritesController::class,'deleteFavourites']);
        Route::post('/add_to_cart',[OrdiioCartController::class,'addToCart']);
        Route::post('/get_favourite_tracks',[OrdiioFavouritesController::class,'getFavourites']);
        Route::post('/get_cart_details',[OrdiioCartController::class,'getCartDetails']);
        Route::post('/create_playlist',[OrdiioPlaylistsController::class,'createPlaylist']);
        Route::post('/deletefromPlaylist',[OrdiioPlaylistsController::class,'deletefromPlaylist']);
        Route::get('/get_playlist',[OrdiioPlaylistsController::class,'getPlaylist']);
        Route::post('/add_to_playlist',[OrdiioPlaylistsController::class,'addToPlaylist']);
        Route::post('/get_playlist_tracks',[OrdiioPlaylistsController::class,'getPlaylisttracks']);
        // Route::post('/getTrackDetails/{trackId}',[OrdiioFavouritesController::class,'getTrackDetails']);
        Route::post('/isSubscriber',[OrdiioController::class,'isSubscriber']);
        Route::post('/isdownloadable',[OrdiioController::class,'isdownloadable']);
        Route::post('/reset_password',[OrdiioController::class,'reset_password']);
        Route::post('/update_company_type',[OrdiioController::class,'update_company_type']);
        Route::post('/about_artist',[SourceAudioApiController::class,'about_artist']);
        Route::post('/make_as_subscriber',[OrdiioController::class,'make_as_subscriber']);
        Route::post('/download_track',[OrdiioController::class,'download_track']);
        Route::post('/licensed_tracks',[SourceAudioApiController::class,'licensed_tracks']);
        Route::post('/museAIsearch',[SourceAudioApiController::class,'museAIsearch']);
        Route::post('/youtube_allowlist',[Ordiio_settings_controller::class,'youtube_allowlist']);
        Route::post('/whitelist_data',[Ordiio_settings_controller::class,'whitelist_data']);
        Route::post('/whitelist_data_remove',[Ordiio_settings_controller::class,'whitelist_data_remove']);
        Route::post('/curated_playlist_tracks',[SourceAudioApiController::class,'curated_playlist_tracks']);
        Route::post('/link_search',[SourceAudioApiController::class,'link_search']);
        Route::post('/stems',[SourceAudioApiController::class,'stems']);
    
    Route::prefix('broadcast')->group(function () {
        Route::get('/list',        [BroadcastController::class, 'index']);
        Route::get('/show{id}',    [BroadcastController::class, 'show']);
        Route::post('/add',       [BroadcastController::class, 'store']);
        Route::put('update/{id}',    [BroadcastController::class, 'update']);
        Route::delete('delete/{id}', [BroadcastController::class, 'destroy']);
        Route::post('/schedules', [BroadcastController::class, 'Schedule']);
        Route::get('/schedules', [BroadcastController::class, 'Schedulelist']);
        });
    Route::prefix('target')->group(function () {
        Route::get('/new',    [BroadcastController::class, 'new']);
        Route::get('/active', [BroadcastController::class, 'active']);
        Route::get('/recent', [BroadcastController::class, 'recent']);
        Route::get('/all',    [BroadcastController::class, 'all']);
     });    

       
    });
    Route::get('/tracks/list', [SourceAudioApiController::class, 'listTracks']);
    Route::post('/tracks/getTrackData',[SourceAudioApiController::class,'getTrackData']);
    Route::get('/tracks/list/filter', [SourceAudioApiController::class, 'listTracks_filter']); 
    Route::post('/sonic-search', [OrdiioController::class, 'sonicSearch']);
    Route::post('/similar_search', [SourceAudioApiController::class, 'similar_search']);
    Route::post('/playlists', [SourceAudioApiController::class, 'get_playlists']);
    Route::post('/get_playlists_thematic_albums', [SourceAudioApiController::class, 'get_playlists_thematic_albums']);
    Route::post('/get_playlists_album_track', [SourceAudioApiController::class, 'get_playlists_album_track']);
    Route::get('/customFilters',[OrdiioFilterController::class,'listTracksFilter']);

 
    // Route::get('/run_agent/{uuid}',[WhapiController::class,'run_agent']);

   //---------------------------------------------------------------------------------------------------------------------------------
     //customerend
        Route::get('/orders/{client_phone}/{customer_phone}', [OrderController::class, 'indexByPhone']);
        Route::get('/appointments/{space_phone}/{customer_phone}', [AppointmentController::class, 'getAppointments']);
        Route::post('/appointments/{space_phone}/{customer_phone}', [AppointmentController::class, 'storeappointment']);
        Route::post('/orders/{client_phone}/{customer_phone}', [OrderController::class, 'storeOrder']);
        Route::get('/products/{phoneNumber}', [ProductsController::class, 'show']);
        Route::get('/services/{phoneNumber}', [ServicesController::class, 'show']);   
        //verify token    
        Route::get('/manual-token-check', [ClientsController::class, 'verifyToken']);
        //Transaction Controller fo payment Customer
        // Route::post('/payments',[TransactionController::class,'create_payment']);
        // Route::post('/create_payment_appointment',[TransactionController::class,'create_payment_appointment']);
        // Route::post('/stripe/webhook', [TransactionController::class, 'handleStripeWebhook']);
        //countries list 
        Route::get('/countries',[ClientsController::class,'countries']);
        Route::get('/available-slots/{space_phone}', [AppointmentController::class, 'getAvailableSlots']);
        //Transaction Details 
        Route::post('store_transaction/{client_phone}/{customer_phone}', [TransactionController::class, 'store_transaction']);
        Route::get('get_transaction/{client_phone}/{customer_phone}', [TransactionController::class, 'get_transaction']);
        Route::put('/products/{id}', [ProductsController::class, 'update']);
        Route::post('/categories',[CategoriesController::class,'add_categories']);
        //business-categories
        Route::get('business_categories', [BusinessCategoryController::class, 'index']);       
        Route::post('business_categories', [BusinessCategoryController::class, 'store']);        
        Route::get('business_categories/{id}', [BusinessCategoryController::class, 'show']);
        //PayStack Controller
        
        // Stripe + Paystack for Orders
        Route::get('/stripe/order/{uuid}', [TransactionController::class, 'payNow'])->name('payment.stripe.order');
        Route::get('/paystack/order/{uuid}', [PayStackController::class, 'initializeOrder'])->name('payment.paystack.order');
        Route::get('/stripe/appointment/{uuid}', [TransactionController::class, 'payNow1'])->name('payment.stripe.appointment');
        Route::get('/paystack/appointment/{uuid}', [PayStackController::class, 'initializeAppointment'])->name('payment.paystack.appointment');
        Route::get('/stripe/instance/{uuid}', [TransactionController::class, 'payNowInstance'])->name('payment.stripe.instance');
        Route::get('/paystack/instance/{uuid}', [PayStackController::class, 'initializeWhapi'])->name('payment.paystack.instance');
        Route::get('/stripe/instance/{uuid}', [TransactionController::class, 'payNowsubscription'])->name('payment.stripe.subscription');
        Route::get('/paystack/subscription/{uuid}', [PayStackController::class, 'initializesubscription'])->name('payment.paystack.subscription');

        // Route::get('/paystack/order/{uuid}', [PayStackController::class, 'initializeOrder']);
        // Route::get('/paystack/appointment/{uuid}', [PayStackController::class, 'initializeAppointment']);
        Route::get('/paystack/whapi/{space_uuid}', [PayStackController::class, 'initializeWhapi']);
        Route::get('/paystack/callback', [PayStackController::class, 'callback'])->name('paystack.callback');
        Route::post('/paystack/webhook', [PayStackController::class, 'webhook']);
        Route::post('/paystack/webhook_test', [PayStackController::class, 'webhook_test']);
        // Route::get('/stripe/order/{uuid}', [TransactionController::class, 'payNow']);
        // Route::get('/stripe/appointment/{uuid}', [TransactionController::class, 'payNow1']);
        Route::post('/payment/status', [TransactionController::class, 'getPaymentStatus']);
        Route::post('/stripe/webhook', [TransactionController::class, 'handleWebhook']);
        // Route::get('/paystack/testorder/{uuid}', [PayStackController::class, 'initializeAppointmenttest']);
        
        
        
        
        Route::post('/delaydogusers/{user_phone}',[DelaydogController::class,'delaydogusers']);
        Route::post('/delaydogjourney/{user_phone}',[DelaydogController::class,'delaydogjourney']);
        Route::post('/delaydogclaims/{user_phone}/{journey_uuid}',[DelaydogController::class,'delaydogclaims']);
        Route::post('/delaydogtickets',[DelaydogController::class,'delayDogTickets']);

        //
        // OrdiioController

        Route::post('/User_register',[OrdiioController::class,'registerUser']);
        Route::post('/User_login',[OrdiioController::class,'loginUser']);
        Route::get('/ordiio-token-check', [OrdiioController::class, 'verifyToken']);
        Route::post('/create_ordiio_license_category',[OrdiioController::class,'create_license_category']);
        Route::post('/ordiio_stripe/webhook', [OrdiioController::class, 'webhook']);
        // Route::post('/ordiio_stripe/webhook', [OrdiioController::class, 'webhook_subs']);
    Route::post('/run-broadcast-cron', function (Request $request) {

     if ($request->header('X-CRON-TOKEN') !== env('CRON_API_TOKEN')) {
        return response()->json(['message' => 'Unauthorized'], 401);
        }

        Artisan::call('broadcast:run');

        return response()->json([
        'success' => true,
        'message' => 'Broadcast cron executed successfully'
        ]);
    });
