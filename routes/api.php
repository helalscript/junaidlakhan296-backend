<?php



use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\Auth\ResetPasswordController;
use App\Http\Controllers\API\Auth\SocialLoginController;
use App\Http\Controllers\API\Auth\UserController;
use App\Http\Controllers\API\V1\AnonymousUserController;
use App\Http\Controllers\API\V1\CMS\HomePageController;
use App\Http\Controllers\API\V1\ParkingSpaceController;
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


Route::group(['middleware' => ['auth:api', 'check_is_host']], function ($router) {
    Route::get('/my-parking-spaces', [ParkingSpaceController::class, 'indexForHost']);
    Route::post('/my-parking-spaces/create', [ParkingSpaceController::class, 'store']);
    Route::post('/my-parking-spaces/update/{ParkingSpaceSlug}', [ParkingSpaceController::class, 'update']);
    Route::get('/my-parking-spaces/single/{ParkingSpaceSlug}', [ParkingSpaceController::class, 'showForHost']);
    Route::delete('/my-parking-spaces/delete/{ParkingSpaceSlug}', [ParkingSpaceController::class, 'destroy']);
});


// public api
Route::get('/parking-spaces', [ParkingSpaceController::class, 'indexForUsers']);
Route::get('/parking-spaces/single/{ParkingSpaceSlug}', [ParkingSpaceController::class, 'showForUsers']);