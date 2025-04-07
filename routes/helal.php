<?php

/**
 * Backend Admin Routes for Web Application
 *
 * This file contains all the routes for managing the admin panel, including routes for
 * dashboard, system Settings, profile Settings, daily tips, blogs, and natural care.
 * Routes are grouped under the 'admin' prefix and require authentication with the 'admin' middleware.
 */


use App\Http\Controllers\Web\Backend\CMS\HomePageController;
use App\Http\Controllers\Web\Backend\CMS\HomePageHowItWorkContainerController;
use App\Http\Controllers\Web\Backend\CMS\HomePageSocialLinkContainerController;
use App\Http\Controllers\Web\Backend\CMS\HomePageWhyChooseUsContainerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Backend\ProfileController;
use App\Http\Controllers\Web\Backend\DynamicPageController;
use App\Http\Controllers\Web\Backend\SystemSettingController;


Route::middleware(['auth:web', 'role_check'])->prefix('admin')->group(function () {
  // Route for the admin dashboard
  // Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');


  // Routes for managing guests
  Route::get('/', function () {
    return view('backend.layouts.dashboard.index');
    // return view('backend.layouts.dashboard.index');
  })->name('admin.dashboard');

  Route::get('settings-profile', [ProfileController::class, 'index'])->name('profile_settings.index');
  Route::post('settings-profile', [ProfileController::class, 'update'])->name('profile_settings.update');
  Route::get('settings-profile-password', [ProfileController::class, 'passwordChange'])->name('profile_settings.password_change');
  Route::post('settings-profile-password', [ProfileController::class, 'UpdatePassword'])->name('profile_settings.password');

  // Route for system settings index
  Route::get('system-settings', [SystemSettingController::class, 'index'])->name('system_settings.index');

  // Route for updating system settings
  Route::post('system-settings-update', [SystemSettingController::class, 'update'])->name('system_settings.update');

  // Mail Settings index
  Route::get('system-settings-mail', [SystemSettingController::class, 'mailSettingGet'])->name('system_settings.mail_get');
  // Mail Settings routes
  Route::post('system-settings-mail', [SystemSettingController::class, 'mailSettingUpdate'])->name('system_settings.mail');
  // Social App Settings
  Route::get('setting-configuration-social', [SystemSettingController::class, 'socialConfigGet'])->name('system_settings.configuration.social_get');
  Route::post('setting-configuration-social', [SystemSettingController::class, 'socialAppUpdate'])->name('system_settings.configuration.social');
  // Payments Settings
  Route::get('setting-configuration-payment', [SystemSettingController::class, 'paymentSettingGet'])->name('system_settings.configuration.payment_get');
  Route::post('setting-configuration-payment', [SystemSettingController::class, 'paymentSettingUpdate'])->name('system_settings.configuration.payment');


  // Routes for DynamicPageController
  Route::resource('/dynamic-page', DynamicPageController::class)->names('dynamic_page');
  Route::post('/dynamic-page/status/{id}', [DynamicPageController::class, 'status'])->name('dynamic_page.status');


  // Route Home Page CMS
  Route::get('/home-page/banner/index', [HomePageController::class, 'index'])->name('cms.home_page.banner.index');
  Route::get('/home-page/banner', [HomePageController::class, 'create'])->name('cms.home_page.banner.create');
  Route::Post('/home-page/banner', [HomePageController::class, 'updateBanner'])->name('cms.home_page.banner.update_banner');

  // Route Social link
  Route::resource('/home-page/social-link/index', HomePageSocialLinkContainerController::class)->names('cms.home_page.social_link')->except('show');
  Route::post('/home-page/social-link/status/{id}', [HomePageSocialLinkContainerController::class, 'status'])->name('cms.home_page.social_link.status');

  // Route home page how it works
  Route::resource('/home-page/how-it-work/index', HomePageHowItWorkContainerController::class)->names('cms.home_page.how_it_work')->except('show');
  Route::post('/home-page/how-it-work/status/{id}', [HomePageHowItWorkContainerController::class, 'status'])->name('cms.home_page.how_it_work.status');
  Route::Post('/cms/home-page/how-it-work-update', [HomePageHowItWorkContainerController::class, 'HowItWorkContainerUpdate'])->name('cms.home_page.how_it_work.how_it_work_update');

  // Route home page why choose us
  Route::resource('/home-page/why-choose-us/index', HomePageWhyChooseUsContainerController::class)->names('cms.home_page.why_choose_us')->except('show');
  Route::post('/home-page/why-choose-us/status/{id}', [HomePageWhyChooseUsContainerController::class, 'status'])->name('cms.home_page.why_choose_us.status');
  Route::Post('/cms/home-page/why-choose-us-update', [HomePageWhyChooseUsContainerController::class, 'WhyChooseUsContainerUpdate'])->name('cms.home_page.why_choose_us.why_choose_us_update');
  
});


// Public route for dynamic pages accessible to all users
Route::get('/pages/{slug}', [DynamicPageController::class, 'showDaynamicPage'])->name('pages');
