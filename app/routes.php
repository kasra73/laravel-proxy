<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/
Route::get('URL/{url}','FileController@showFile')->where('url', '(.*)');
Route::any('encryptURL/{url}','FileController@loadPage')->where('url', '(.*)');
Route::any('encryptAds/{url}','FileController@loadPageAds')->where('url', '(.*)');
Route::any('BBBase64/{url}','FileController@base64loader')->where('url', '(.*)');
//Route::get('{url}', 'FileController@directController')->where('url', '(.*)');
Route::post('inputURL','FileController@goInput');
Route::get('inputURL', function()
{
	return View::make('main');
});

Route::get('/', function()
{
	return View::make('main');
});

Route::get('fb',function(){
    return Redirect::secure('encryptURL/'.Crypt::encrypt('https://www.facebook.com/'));
});
Route::get('mfb',function(){
    return Redirect::secure('encryptURL/'.Crypt::encrypt('https://m.facebook.com/'));
});
Route::get('tw',function(){
    return Redirect::secure('encryptURL/'.Crypt::encrypt('https://www.twitter.com/'));
});
Route::get('glg',function(){
    return Redirect::secure('encryptURL/'.Crypt::encrypt('https://www.google.com/'));
});
Route::get('mtw',function(){
    return Redirect::secure('encryptURL/'.Crypt::encrypt('https://m.twitter.com/'));
});