<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Note that middleware and namespace prefix is defined in bootstrap/app.php
|
*/


$app->get('/primo/search', 'PrimoController@search');
// Returns a list of PrimoRecord and PrimoRecordGroup

$app->get('/primo/groups/{id}', 'PrimoController@getGroup');
// Returns a list of PrimoRecord belonging to a PrimoRecordGroup

$app->get('/primo/records/{id}', 'PrimoController@getRecord');
// Returns a single PrimoRecord

$app->get('/primo/records/{id}/cover', 'PrimoController@getCover');
// Returns cover data for a single PrimoRecord

$app->get('/subjects/search', 'SubjectsController@search');
// Returns a list of Subject

$app->get('/subjects/show/{vocab}/{id}', 'SubjectsController@show');
// Returns a Subject

$app->get('/subjects/lookup', 'SubjectsController@lookup');
// Returns a Subject

$app->get('/alma/search', ['as' => 'alma.search', 'uses' => 'AlmaController@search']);
// Returns a single AlmaRecord

$app->get('/alma/records/{id}', ['as' => 'alma.get', 'uses' => 'AlmaController@getRecord']);
// Returns a single AlmaRecord


$app->get('/stats', 'StatsController@index');
// Returns a list of PrimoRecord and PrimoRecordGroup
