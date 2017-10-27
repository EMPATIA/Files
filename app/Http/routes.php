<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Files Routes
|--------------------------------------------------------------------------
|
| This route group applies the «Files» group to every route
| it contains.
|
*/

Route::post('file/upload', 'FilesController@upload');
Route::get('file/download/{id}/{code}/{inline?}', 'FilesController@download');
Route::get('file/list', 'FilesController@index');
Route::get('file/listImages', 'FilesController@indexImages');
Route::get('file/genUploadKey', 'FilesController@genUploadKey');
Route::post('file/listFiles', 'FilesController@getListFiles');
Route::resource('file', 'FilesController', ['only' => ['show', 'store', 'update', 'destroy']]);

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    //
});

