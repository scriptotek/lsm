<?php

namespace App\Http\Controllers;

use App\PrimoCover;
use App\PrimoSearch;
use App\PrimoException;
use Guzzle\Http\Exception\BadResponseException;
use Illuminate\Http\Request;

class PrimoController extends Controller
{

    protected function handleErrors($requestFn)
    {
        try {
            $data = $requestFn();
        } catch (PrimoException $e) {
            return response()->json([
                'results' => [],
                'error' => $e->getMessage(),
                'source' => $e->getUrl(),
            ], 400);
        } catch (BadResponseException $e) {
            return response()->json([
                'results' => [],
                'error' => $e->getMessage(),
                'source' => $e->getRequest()->getUrl(),
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
     *     name="genre",
     *     in="query",
     *     description="One or more form/genre terms, separated by `OR`. Not limited to a specific vocabulary.",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="place",
     *     in="query",
     *     description="One or more geographical names, separated by `OR`. Not limited to a specific vocabulary.",
     *     type="string"
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
     *     description="One or more subject terms. Boolean operators `AND` and `OR` are supported, with `AND` taking precedence over `OR`. Grouping with parentheses are not supported.",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="vocabulary",
     *     in="query",
     *     description="Subject vocabulary. Used as a qualifier with the subject field, leave blank to search all subject vocabularies.",
     *     type="string",
     *     enum={"realfagstermer", "humord", "tekord", "mrtermer"}
     *   ),
     *   @SWG\Parameter(
     *     name="material",
     *     in="query",
     *     description="Comma-separated lisf of material types (example: `print-books,print-journals` or `e-books,e-journals`). By default, all material types are included. No truncation is supported.",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="institution",
     *     in="query",
     *     description="Limit to a institution. Example: `UBO`. Case insensitive.",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="library",
     *     in="query",
     *     description="Limit to one or more comma-separated library codes. Examples: `ubo1030310,ubo1030317` for Realfagsbiblioteket and Informatikkbiblioteket. Case insensitive. Warning: ebooks will be excluded when setting `library` since ebooks are not linked to a library code anymore (except for a few thousand errors…).",
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
     *     description="Sort field, defaults to relevance.",
     *     type="string",
     *     default="relevance",
     *     enum={"relevance", "popularity", "date", "author", "title"}
     *   ),
     *   @SWG\Parameter(
     *     name="repr",
     *     in="query",
     *     description="Result representation format. `compact=repr` returns a more simplifed representation, suitable for e.g. limited bandwidth. `compact=full` includes more information. This parameter has no effect on groups, only records.",
     *     type="string",
     *     default="compact",
     *     enum={"compact", "full"}
     *   )
     * )
     *
     * @param  PrimoSearch  $search
     * @param  Request  $request
     * @return Response
     */
    public function search(PrimoSearch $search, Request $request)
    {
        if ($request->get('raw') == 'true') {
            return response()->make($search->search($request), 200, ['Content-Type' => 'application/xml']);
        }
        return $this->handleErrors(function() use ($search, $request) {
            return $search->search($request);
        });
    }

    /**
     * @SWG\Get(
     *   path="/primo/records/{id}",
     *   description="Get details about a single record",
     *   tags={"Primo"},
     *   produces={"application/json", "application/xml"},
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
     *   ),
     *   @SWG\Parameter(
     *     name="raw",
     *     in="query",
     *     description="Set to true to return the raw PNX record.",
     *     type="boolean",
     *     default="false"
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
        if ($request->get('raw') == 'true') {
            return response()->make($search->getRecord($id, $request), 200, ['Content-Type' => 'application/xml']);
        }
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
        if ($request->get('raw') == 'true') {
            return response()->make($search->getGroup($id, $request), 200, ['Content-Type' => 'application/xml']);
        }
        return $this->handleErrors(function() use ($search, $request, $id) {
            return $search->getGroup($id, $request);
        });
    }

    /**
     * @SWG\Get(
     *   path="/primo/records/{id}/cover",
     *   description="Get cover image data for a given record.",
     *   tags={"Primo"},
     *   produces={"image"},
     *   @SWG\Response(
     *     response=302,
     *     description="Redirect to image file"
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
     * @param  PrimoCover  $cover
     * @param  Request  $request
     * @param  int  $recordId
     * @return Response
     */
    public function getCover(PrimoCover $cover, Request $request, $recordId)
    {
        return $cover->getCover($recordId, $request);
    }

}
