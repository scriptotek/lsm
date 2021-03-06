<?php

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::get('saml2/error', 'Auth\LoginController@error');

Route::group(['middleware' => ['session', 'auth']], function () {
    Route::post('logout', 'Auth\LoginController@samlLogout')->name('logout');
});

Route::get('account', 'Auth\LoginController@account')->name('account');

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

Route::get('/', 'HomeController@index');


Route::get('primo/search', 'PrimoController@search');
// Returns a list of PrimoRecord and PrimoRecordGroup

Route::get('primo/v2/search', 'PrimoController@searchV2');
Route::get('primo/v2/configuration', 'PrimoController@configuration');
Route::get('primo/v2/translations', 'PrimoController@translations');

Route::get('primo/groups/{id}', 'PrimoController@getGroup');
// Returns a list of PrimoRecord belonging to a PrimoRecordGroup

Route::get('primo/records/{id}', 'PrimoController@getRecord');
// Returns a single PrimoRecord

Route::get('primo/records/{id}/cover', 'PrimoController@getCover');
// Returns cover data for a single PrimoRecord

Route::get('subjects/search', 'SubjectsController@search');
// Returns a list of Subject

Route::get('subjects/show/{vocab}/{id}', 'SubjectsController@show');
// Returns a Subject

Route::get('subjects/lookup', 'SubjectsController@lookup');
// Returns a Subject

Route::get('alma/search', ['as' => 'alma.search', 'uses' => 'AlmaController@search']);
// Returns a single AlmaRecord

Route::get('alma/records/{id}/holdings/{holding_id}', ['as' => 'alma.items', 'uses' => 'AlmaController@items']);
Route::get('alma/records/{id}/holdings', ['as' => 'alma.holdings', 'uses' => 'AlmaController@holdings']);
Route::get('alma/records/{id}', ['as' => 'alma.get', 'uses' => 'AlmaController@bib']);
// Returns a single AlmaRecord


Route::get('stats', 'StatsController@index');
// Returns a list of PrimoRecord and PrimoRecordGroup
