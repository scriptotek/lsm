<?php

namespace App\Http\Controllers;

use App\PrimoCover;
use App\PrimoSearch;
use App\PrimoException;
use App\PrimoSearchV2;
use Http\Client\Exception\HttpException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        } catch (HttpException $e) {
            return response()->json([
                'results' => [],
                'error' => $e->getMessage(),
                'errorDetails' => strval($e->getResponse()->getBody()),
            ], 400);
        }
        return response()->json($data);
    }

    /**
     * @OA\Schema(
     *    schema="PrimoSearchErrorResponse",
     *    required={"error"},
     *    @OA\Schema(schema="error", type="string", description="Error message."),
     *    @OA\Schema(schema="source", type="string", description="URL for the Primo Brief Search API call.")
     * ),
     *
     * @OA\Get(
     *   path="/primo/search",
     *   summary="Search Primo records using the old XServices API",
     *   description="Search using either a free text query with `query`, or a controlled subject query using `vocabulary` and `subject` in combination. Pagination: If there's no more results, `next` will be null. Otherwise `next` will hold the value to be used with `first` to get the next batch of results. Returns: a list of Primo records (`type: record`) and groups of Primo records (`type: group`). Groups can be expanded using the `/primo/group` endpoint. To automatically expand all groups, set 'expand_groups' to true, but note that this will effect the response time substantially.",
     *   tags={"Primo"},
     *   @OA\Parameter(
     *     name="genre",
     *     in="query",
     *     description="One or more form/genre terms, separated by `OR`. Not limited to a specific vocabulary.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="place",
     *     in="query",
     *     description="One or more geographical names, separated by `OR`. Not limited to a specific vocabulary.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="query",
     *     in="query",
     *     description="Query string",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="raw_query",
     *     in="query",
     *     description="Raw query string on the form 'field,operator,term'. Example: 'lsr05,exact,urealsamling42 AND lsr05,exact,urealboksamling'. Multiple queries can be combined with AND, but OR is not supported.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="subject",
     *     in="query",
     *     description="One or more subject terms. Boolean operators `AND` and `OR` are supported, with `AND` taking precedence over `OR`. Grouping with parentheses are not supported.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="vocabulary",
     *     in="query",
     *     description="Subject vocabulary. Used as a qualifier with the subject field, leave blank to search all subject vocabularies.",
     *     @OA\Schema(
     *       type="string",
     *       enum={"realfagstermer", "humord", "tekord", "mrtermer", "agrovoc", "nlm", "geo", "ddc", "udc", "ubo"}
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="material",
     *     in="query",
     *     description="Comma-separated lisf of material types (example: `print-books,print-journals` or `e-books,e-journals`). By default, all material types are included. No truncation is supported.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="scope",
     *     in="query",
     *     description="Search scope. Defaults to `BIBSYS_ILS`.",
     *     @OA\Schema(
     *       type="string",
     *       default="BIBSYS_ILS",
     *       enum={"BIBSYS_ILS", "UBO"}
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="institution",
     *     in="query",
     *     description="Limit to a institution. Example: `UBO`. Case insensitive.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="library",
     *     in="query",
     *     description="Limit to one or more comma-separated library codes. Examples: `ubo1030310,ubo1030317` for Realfagsbiblioteket and Informatikkbiblioteket. Case insensitive. Warning: ebooks will be excluded when setting `library` since ebooks are not linked to a library code anymore (except for a few thousand errors…).",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="start",
     *     in="query",
     *     description="First document to retrieve, starts at 1.",
     *     @OA\Schema(
     *       type="integer",
     *       minimum=1
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Number of documents to retrieve, defaults to 10, maximum is 50.",
     *     @OA\Schema(
     *       type="integer",
     *       minimum=1,
     *       maximum=50
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="sort",
     *     in="query",
     *     description="Sort field, defaults to relevance.",
     *     @OA\Schema(
     *       type="string",
     *       default="relevance",
     *       enum={"relevance", "popularity", "date", "author", "title"}
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="repr",
     *     in="query",
     *     description="Result representation format. `compact=repr` returns a more simplifed representation, suitable for e.g. limited bandwidth. `compact=full` includes more information. This parameter has no effect on groups, only records.",
     *     @OA\Schema(
     *       type="string",
     *       default="compact",
     *       enum={"compact", "full"}
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="expand_groups",
     *     in="query",
     *     description="Expand all groups. Note that this will substantially increase response time as we need to make one request to Primo for each group.",
     *     @OA\Schema(
     *       type="boolean",
     *       default=false
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="success",
     *     @OA\MediaType(
     *         mediaType="application/json"
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="error",
     *     @OA\JsonContent(ref="#/components/schemas/PrimoSearchErrorResponse")
     *   ),
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
        return $this->handleErrors(function () use ($search, $request) {
            return $search->search($request);
        });
    }

    /**
     * @OA\Get(
     *   path="/primo/search-v2",
     *   summary="Search Primo records using the new Primo Search REST API",
     *   description="Search using the 'new' Primo REST API.",
     *   tags={"Primo"},
     *   @OA\Response(
     *     response=200,
     *     description="success"
     *   ),
     *   @OA\Parameter(
     *     name="q",
     *     in="query",
     *     description="Query string",
     *     example="any,contains,origin of species",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="scope",
     *     in="query",
     *     description="Search scope, defaults to config default value.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="inst",
     *     in="query",
     *     description="Primo institution, defaults to config default value.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="vid",
     *     in="query",
     *     description="View id, defaults to config default value.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="lang",
     *     in="query",
     *     description="Language, defaults to config default value.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="tab",
     *     in="query",
     *     description="Search tab, defaults to config default value.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="offset",
     *     in="query",
     *     description="The offset of the results from which to start displaying the results, defaults to 0.",
     *     @OA\Schema(
     *       type="integer",
     *       minimum=0,
     *       default=0
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Number of documents to retrieve, defaults to 10, maximum is 50.",
     *     @OA\Schema(
     *       type="integer",
     *       minimum=1,
     *       maximum=50,
     *       default=10
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="sort",
     *     in="query",
     *     description="Sort field, defaults to relevance.",
     *     @OA\Schema(
     *       type="string",
     *       default="relevance",
     *       enum={"relevance", "popularity", "date", "date2", "author", "title"}
     *     )
     *   )
     * )
     */
    public function searchV2(PrimoSearchV2 $search, Request $request)
    {
        return $this->handleErrors(function () use ($search, $request) {
            return $search->search($request->input());
        });
    }

    /**
     * @OA\Get(
     *   path="/primo/records/{id}",
     *   summary="Find Primo record by PNX ID",
     *   description="Get details about a single record",
     *   tags={"Primo"},
     *   @OA\Response(
     *     response=200,
     *     description="A PrimoRecord"
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Primo PNX ID",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="raw",
     *     in="query",
     *     description="Set to true to return the raw PNX record.",
     *     @OA\Schema(
     *       type="boolean",
     *       default=false
     *     )
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
        return $this->handleErrors(function () use ($search, $request, $id) {
            return $search->getRecord($id, $request);
        });
    }

    /**
     * @OA\Get(
     *   path="/primo/groups/{id}",
     *   summary="Find Primo records belonging to some FRBR group",
     *   description="Get a list of records belonging to a record group.",
     *   tags={"Primo"},
     *   @OA\Response(
     *     response=200,
     *     description="A PrimoRecordGroup"
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="FRBR group id",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
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
        return $this->handleErrors(function () use ($search, $request, $id) {
            return $search->getGroup($id, $request);
        });
    }

    /**
     * @OA\Get(
     *   path="/primo/records/{id}/cover",
     *   summary="Find cover image for a given Primo record",
     *   description="Get cover image data for a given record.",
     *   tags={"Primo"},
     *   @OA\Response(
     *     response=302,
     *     description="Redirect to image file"
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Primo PNX ID",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
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
