<?php

namespace App\Http\Controllers;

use App\PrimoSearch;
use App\PrimoException;
use Illuminate\Http\Request;

class PrimoController extends Controller
{

    protected function handleErrors($requestFn)
    {
        try {
            $data = $requestFn();
        } catch (PrimoException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'source' => $e->getUrl()
            ], 400);
        }
        return response()->json($data);
    }

    /**
     * @SWG\Definition(
     *    definition="PrimoSearchErrorResponse",
     *    required={"error"},
     *    @SWG\Property(property="error", type="string", description="Error message."),
     *    @SWG\Property(property="source", type="string", description="URL for the Primo Brief Search API call.")
     * ),
     * @SWG\Get(
     *   path="/primo/search",
     *   description="Search using either a free text query with `query`, or a controlled subject query using `vocabulary` and `subject` in combination. Pagination: If there's no more results, `next` will be null. Otherwise `next` will hold the value to be used with `first` to get the next batch of results. Returns: a list of Primo records (`type: record`) and groups of Primo records (`type: group`). Groups can be expanded using the `/primo/group` endpoint.",
     *   tags={"Primo"},
     *   produces={"application/json"},
     *   @SWG\Response(
     *     response=200,
     *     description="success"
     *   ),
     *   @SWG\Response(
     *     response=400,
     *     description="error",
     *     @SWG\Schema(ref="#/definitions/PrimoSearchErrorResponse")
     *   ),
     *   @SWG\Parameter(
     *     name="query",
     *     in="query",
     *     description="Query string",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="subject",
     *     in="query",
     *     description="Subject term",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="vocabulary",
     *     in="query",
     *     description="Subject vocabulary",
     *     type="string",
     *     enum={"realfagstermer", "humord", "tekord", "mrtermer"}
     *   ),
     *   @SWG\Parameter(
     *     name="material",
     *     in="query",
     *     description="Limit to a given material type (print or electronic). By default, all material types are included.",
     *     type="string",
     *     enum={"print-books", "e-books"}
     *   ),
     *   @SWG\Parameter(
     *     name="library",
     *     in="query",
     *     description="Limit to one or more library codes. Supports truncation with `*`, so `ureal*` will include 'urealinf' as well. Boolean logic is supported, example: `ureal* OR ubonett` ('ubonett' is the library code used for all e-books at UBO).",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="start",
     *     in="query",
     *     description="First document to retrieve, starts at 1.",
     *     type="integer"
     *   ),
     *   @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Number of documents to retrieve, defaults to 10, maximum is 50.",
     *     type="integer"
     *   ),
     *   @SWG\Parameter(
     *     name="sort",
     *     in="query",
     *     description="Sort field, defaults to popularity.",
     *     type="string",
     *     default="popularity",
     *     enum={"popularity", "date", "author", "title"}
     *   )
     * )
     *
     * @param  PrimoSearch  $search
     * @param  Request  $request
     * @return Response
     */
    public function search(PrimoSearch $search, Request $request)
    {
        return $this->handleErrors(function() use ($search, $request) {
            return $search->search($request);
        });
    }

    /**
     * @SWG\Get(
     *   path="/primo/records/{id}",
     *   description="Get details about a single record",
     *   tags={"Primo"},
     *   produces={"application/json"},
     *   @SWG\Response(
     *     response=200,
     *     description="A PrimoRecord"
     *   ),
     *   @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     description="Primo PNX ID",
     *     required=true,
     *     type="string"
     *   )
     * )
     *
     * @param  PrimoSearch  $search
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function getRecord(PrimoSearch $search, Request $request, $id)
    {
        return $this->handleErrors(function() use ($search, $request, $id) {
            return $search->getRecord($id, $request);
        });
    }

    /**
     * @SWG\Get(
     *   path="/primo/groups/{id}",
     *   description="Get a list of records belonging to a record group.",
     *   tags={"Primo"},
     *   produces={"application/json"},
     *   @SWG\Response(
     *     response=200,
     *     description="A PrimoRecordGroup"
     *   ),
     *   @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     description="FRBR group id",
     *     required=true,
     *     type="string"
     *   )
     * )
     *
     * @param  PrimoSearch  $search
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function getGroup(PrimoSearch $search, Request $request, $id)
    {
        return $this->handleErrors(function() use ($search, $request, $id) {
            return $search->getGroup($id, $request);
        });
    }

}