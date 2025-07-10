<?php



use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\Auth\ResetPasswordController;
use App\Http\Controllers\API\Auth\SocialLoginController;
use App\Http\Controllers\API\Auth\UserController;
use App\Http\Controllers\API\V1\CMS\HomePageController;
use App\Http\Controllers\API\V1\Host\HostParkingSpaceController;
use App\Http\Controllers\API\V1\Host\Reservation\HostReservationController;
use App\Http\Controllers\API\V1\User\NotificationController;
use App\Http\Controllers\API\V1\User\StripePaymentController;
use App\Http\Controllers\API\V1\User\UserBookingController;
use App\Http\Controllers\API\V1\User\UserContactSupportController;
use App\Http\Controllers\API\V1\User\UserFaqController;
use App\Http\Controllers\API\V1\User\UserNotificationSettingController;
use App\Http\Controllers\API\V1\User\UserParkingSpaceController;
use App\Http\Controllers\API\V1\User\UserReviewController;
use App\Http\Controllers\API\V1\User\UserVehicleController;
use Illuminate\Support\Facades\Route;



Route::group(['middleware' => 'guest:api'], function ($router) {
    //register
    Route::post('register', [RegisterController::class, 'register']);
    Route::post('/verify-email', [RegisterController::class, 'VerifyEmail']);
    Route::post('/resend-otp', [RegisterController::class, 'ResendOtp']);
    //login
    Route::post('login', [LoginController::class, 'login']);
    //forgot password
    Route::post('/forget-password', [ResetPasswordController::class, 'forgotPassword']);
    Route::post('/verify-otp', [ResetPasswordController::class, 'VerifyOTP']);
    Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']);
    //social login
    Route::post('/social-login', [SocialLoginController::class, 'SocialLogin']);

});

Route::group(['middleware' => 'auth:api'], function ($router) {
    Route::get('/refresh-token', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/update-profile', [UserController::class, 'updateProfile']);
    Route::post('/update-password', [UserController::class, 'changePassword']);
    Route::delete('/delete-profile', [UserController::class, 'deleteProfile']);
});

// --------- cms part --------------
//home page
Route::get('/cms/home-page/banner', [HomePageController::class, 'getHomeBanner']);
Route::get('/cms/home-page/how-it-work', [HomePageController::class, 'getHomeHowitWork']);
Route::get('/cms/home-page/why-choose-us', [HomePageController::class, 'getHomeWhyChooseUs']);

Route::get('/cms/social-link', [HomePageController::class, 'getSocialLinks']);
Route::get('/cms/system-info', [HomePageController::class, 'getSystemInfo']);

// dynamic page
Route::get("dynamic-pages", [HomePageController::class, "getDynamicPages"]);
Route::get("dynamic-pages/single/{slug}", [HomePageController::class, "showDaynamicPage"]);

// Route::group(['middleware' => 'check_anonymous_user'], function ($router) {

// });



//only for host
Route::group(['middleware' => ['auth:api', 'check_is_host']], function ($router) {
    Route::get('/host-dashboard-data', [HostReservationController::class, 'hostDashboardData']);
    Route::get('/host-dashboard-transactions', [HostReservationController::class, 'hostDashboardTransactions']);
    Route::get('/my-parking-spaces', [HostParkingSpaceController::class, 'indexForHost']);
    Route::post('/my-parking-spaces/create', [HostParkingSpaceController::class, 'store']);
    Route::post('/my-parking-spaces/update/{ParkingSpaceSlug}', [HostParkingSpaceController::class, 'update']);
    Route::get('/my-parking-spaces/single/{ParkingSpaceSlug}', [HostParkingSpaceController::class, 'showForHost']);
    Route::delete('/my-parking-spaces/delete/{ParkingSpaceSlug}', [HostParkingSpaceController::class, 'destroy']);
    Route::post('/my-parking-spaces/update-spot-detail/{spotDetailId}', [HostParkingSpaceController::class, 'updateSpotDetail']);
    Route::apiResource('/my-reservations', HostReservationController::class)->only('index', 'show');
    Route::post('/my-reservations/accept/{ReservationUniqueId}', [HostReservationController::class, 'acceptReservation']);
    Route::post('/my-reservations/cancle/{ReservationUniqueId}', [HostReservationController::class, 'cancelReservation']);
});


// only for user
Route::group(['middleware' => ['auth:api', 'check_is_user']], function ($router) {
    Route::get('/user-dashboard-data', [UserBookingController::class, 'userDashboardData']);
    Route::get('/user-dashboard-transactions', [UserBookingController::class, 'userDashboardTransactions']);
    Route::apiResource('/my-bookings', UserBookingController::class)->only('index', 'show', 'store');
    Route::post('/my-bookings/extend-request', [UserBookingController::class, 'bookingExtendRequestView']);
    Route::post('/my-bookings/extend-request-create', [UserBookingController::class, 'bookingExtendRequestStore']);
    Route::post('/my-bookings/given-review', [UserReviewController::class, 'store']);
    Route::apiResource('/my-vehicles', UserVehicleController::class);
    Route::post('/payments/stripe', [StripePaymentController::class, 'createPaymentIntent']);
    // Route::get('/promo-code/test/{userId}/{code}', [StripePaymentController::class, 'assignPromoCodeToUser']); //for test
});

//payments webhook
Route::post('/payments-create/stripe', [StripePaymentController::class, 'handleWebhook']);

// only for user and host
Route::group(['middleware' => ['auth:api', 'check_is_user_or_host']], function ($router) {
    // Route::post('/payments-refund/stripe', [StripePaymentController::class, 'refundPayment']);
    Route::get('/faqs', [UserFaqController::class, 'index']);
    Route::post('/contact-support-message/sent', [UserContactSupportController::class, 'store']);
    Route::apiResource('/my-notifications-setting', UserNotificationSettingController::class)->only('index', 'update');

    //Notifications route
    Route::get('/my-notifications', [NotificationController::class, 'index']);
    Route::post('/my-notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::delete('/my-notifications-delete/{id}', [NotificationController::class, 'delete']);
    Route::delete('/my-notifications-delete-all', [NotificationController::class, 'deleteAll']);
});


// public api
Route::get('/parking-spaces', [UserParkingSpaceController::class, 'indexForUsers']);
Route::get('/parking-spaces/hourly', [UserParkingSpaceController::class, 'indexForUsersHourly']);
Route::get('/parking-spaces/hourly/single/{id}', [UserParkingSpaceController::class, 'showForUsersHourly']);
Route::get('/parking-spaces/daily', [UserParkingSpaceController::class, 'indexForUsersDaily']);
Route::get('/parking-spaces/daily/single/{id}', [UserParkingSpaceController::class, 'showForUsersDaily']);
Route::get('/parking-spaces/monthly', [UserParkingSpaceController::class, 'indexForUsersMonthly']);
Route::get('/parking-spaces/monthly/single/{id}', [UserParkingSpaceController::class, 'showForUsersMonthly']);
// Route::get('/parking-spaces/single/{ParkingSpaceSlug}', [UserParkingSpaceController::class, 'showForUsers']);