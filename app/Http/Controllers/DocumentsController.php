<?php

namespace App\Http\Controllers;

use App\SimpleSearch;
use Illuminate\Http\Request;

class DocumentsController extends Controller
{

    /**
     * Search for documents
     *
     * @SWG\Get(
     *   path="/documents",
     *   summary="Search for documents",
     *   description="Returns simplified representations of Primo PNX records for easy processing, based on either a free text search using `query`, or a controlled subject search using `vocabulary` and `subject` in combination. Pagination: If there's no more results, `next` will be null. Otherwise `next` will hold the value to be used with `first` to get the next batch of results.",
     *   tags={"Documents"},
     *   produces={"application/json"},
     *   @SWG\Response(
     *     response=200,
     *     description="A list of documents"
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
     *     name="frbr_group_id",
     *     in="query",
     *     description="FRBR group ID. Use to expand a group of documents. Should normally not be used together with other search parameters.",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="format",
     *     in="query",
     *     description="Limit to a given physical form (print or electronic). By default, all forms are included.",
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
     * @param  Request  $request
     * @return Response
     */
    public function search(Request $request, SimpleSearch $search)
    {
        $query = $request->get('query');
        $format = $request->get('format');
        $library = $request->get('library');

        $data = $search->search($request);
        return response()->json($data);
    }

    /**
     * Get a single document
     *
     * @SWG\Get(
     *   path="/documents/{id}",
     *   summary="Get details about a single document",
     *   tags={"Documents"},
     *   produces={"application/json"},
     *   @SWG\Response(
     *     response=200,
     *     description="A single document"
     *   ),
     *   @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     description="Document ID (TODO: Specify what kind of ID)",
     *     required=true,
     *     type="string"
     *   )
     * )
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function show(Request $request, SimpleSearch $search, $id)
    {
        $data = $search->lookupDocument($id, $request);
        return response()->json($data);
    }


}