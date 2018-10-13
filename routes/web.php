<?php

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

$this->get('saml2/error', 'Auth\LoginController@error');

Route::group(['middleware' => ['session', 'auth']], function () {
    $this->post('logout', 'Auth\LoginController@samlLogout')->name('logout');
});

$this->get('account', 'Auth\LoginController@account')->name('account');

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

Route::get('/', 'HomeController@index');


Route::get('primo/search', 'PrimoController@search');
// Returns a list of PrimoRecord and PrimoRecordGroup

Route::get('primo/search-v2', 'PrimoController@searchV2');

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

Route::get('alma/records/{id}', ['as' => 'alma.get', 'uses' => 'AlmaController@getRecord']);
// Returns a single AlmaRecord


Route::get('stats', 'StatsController@index');
// Returns a list of PrimoRecord and PrimoRecordGroup
