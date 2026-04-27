<?php
// use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\Category\IntermediateCategoryController;
use App\Http\Controllers\Pages\Category\ProductCategoryController;
use App\Http\Controllers\Pages\Category\GfCategoryController;
use App\Http\Controllers\Pages\Category\MfCategoryController;

use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::prefix('/category')
        ->name('pages.category.')
        ->middleware(CheckLogin::class)
        ->group(function () {

                Route::prefix('/intermediate')
                        ->name('intermediate.')
                        ->controller(IntermediateCategoryController::class)
                        ->group(function () {
                                Route::get('', 'index')->name('list');
                                Route::post('store', 'store')->name('store');
                                Route::post('update', 'update')->name('update');
                                Route::post('deActive', 'deActive')->name('deActive');
                                Route::post('recipe', 'recipe')->name('recipe');
                                Route::post('save_bom', 'save_bom')->name('resave_bomcipe');
                        });


                Route::prefix('/product')
                        ->name('product.')
                        ->controller(ProductCategoryController::class)
                        ->group(function () {
                                Route::get('', 'index')->name('list');
                                Route::post('store', 'store')->name('store');
                                Route::post('update', 'update')->name('update');
                                Route::post('deActive', 'deActive')->name('deActive');
                                Route::post('recipe', 'recipe')->name('recipe');
                                Route::post('save_bom', 'save_bom')->name('save_bom');

                                Route::get('getJsonFPCategory', 'getJsonFPCategory')->name('getJsonFPCategory');
                        });


                Route::prefix('/gf')
                        ->name('gf.')
                        ->controller(GfCategoryController::class)
                        ->group(function () {
                                Route::get('', 'index')->name('list');
                                Route::post('store', 'store')->name('store');
                                Route::post('update', 'update')->name('update');
                                Route::get('delete', 'delete')->name('delete');
                        });

                Route::prefix('/mf')
                        ->name('mf.')
                        ->controller(MfCategoryController::class)
                        ->group(function () {
                                Route::get('', 'index')->name('list');
                                Route::post('store', 'store')->name('store');
                                Route::post('update', 'update')->name('update');
                                Route::get('delete', 'delete')->name('delete');
                        });
        });
