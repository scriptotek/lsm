<?php

/**
 * @SWG\Swagger(
 *   schemes={"https"},
 *   host="lsm.biblionaut.net",
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
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->group(['middleware' => 'cors', 'namespace' => 'App\Http\Controllers'], function ($app) {

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

});
