<?php

/**
 * @SWG\Swagger(
 *   schemes={"https"},
 *   host="scs.biblionaut.net",
 *   basePath="/",
 *   @SWG\Info(
 *     title="Simple Catalogue Search",
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

$app->get('/', function () use ($app) {
    return view('welcome');
});

$app->get('/primo/search', 'PrimoController@search');
// Returns a list of PrimoRecord and PrimoRecordGroup

$app->get('/primo/groups/{id}', 'PrimoController@getGroup');
// Returns a list of PrimoRecord belonging to a PrimoRecordGroup

$app->get('/primo/records/{id}', 'PrimoController@getRecord');
// Returns a single PrimoRecord
