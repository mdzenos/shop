<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\Api\ImportProject;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('index');
});
Route::post('/import/department', [ImportProject::class,'import']);


Route::group(['prefix' => 'admin'], function () {

    Route::group(['prefix' => 'auth'], function () {
        Route::get('/login', function () {
            return view('auth.login');
        })->name('login');
        Route::post('/login', [AccountController::class, 'login']);
        Route::get('/register', function () {
            return view('auth.register');
        });
        Route::post('/register', [AccountController::class, 'register']);
    });


    Route::group(['middleware' => 'jwt.verify'], function () {

        Route::get('/home', function () {
            return redirect()->route('dashboard');
        });
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::get('/logout', [AccountController::class, 'logout'])->name('logout');
        Route::get('/change-password', function () {
            return view('auth.change-password');
        })->name('changePassword');
        Route::post('/change-password', [AccountController::class, 'storePassword']);

        Route::post('import', [ProductController::class, 'import'])->name('import.product');
        Route::get('export', [ProductController::class, 'export'])->name('export.product');
        Route::post('upload', [UploadController::class, 'store']);
        Route::get('resize-image', [ImageController::class, 'resizeImage'])->name('form.image');
        Route::post('resize-image', [ImageController::class, 'resize'])->name('resize.image');
        
        #Upload
        Route::post('upload/services', [\App\Http\Controllers\Admin\UploadController::class, 'store']);
        
        Route::group(['prefix' => 'product'], function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::get('add', [ProductController::class, 'create']);
            Route::post('add', [ProductController::class, 'store']);
            Route::get('list', [ProductController::class, 'index'])->name('dashboard');
            Route::get('edit/{product}', [ProductController::class, 'show']);
            Route::post('edit/{product}', [ProductController::class, 'update']);
            Route::DELETE('destroy', [ProductController::class, 'destroy']);
        });

        Route::group(['prefix' => 'menu'], function () {
            Route::get('/', [MenuController::class, 'index']);
            Route::get('add', [MenuController::class, 'create']);
            Route::post('add', [MenuController::class, 'store']);
            Route::get('list', [MenuController::class, 'index']);
            Route::get('edit/{menu}', [MenuController::class, 'show']);
            Route::post('edit/{menu}', [MenuController::class, 'update']);
            Route::DELETE('destroy', [MenuController::class, 'destroy']);
        });

        
    });
});
