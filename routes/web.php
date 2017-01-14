<?php

/**
 * @SWG\Swagger(
 *   schemes={"https"},
 *   host="ub-lsm.uio.no",
 *   basePath="/",
 *   @SWG\Info(
 *     title="University of Oslo Library Services Middleware (LSM)",
 *     version="0.1"
 *   )
 * )
 * @SWG\Tag(
 *   name="Documents"
 * )
 */

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

$app->get('/alma/records/{id}', 'AlmaController@getRecord');
// Returns a single AlmaRecord
