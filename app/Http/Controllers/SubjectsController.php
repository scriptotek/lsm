<?php

namespace App\Http\Controllers;

use App\Skosmos;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubjectsController extends Controller
{

    /**
     * @OA\Get(
     *   path="/subjects/search",
     *   summary="Search authority records using the Skosmos API",
     *   description="Search for terms, optionally filtered by vocabulary and concept type.",
     *   tags={"Authorities"},
     *   @OA\Response(
     *     response=200,
     *     description="success"
     *   ),
     *   @OA\Parameter(
     *     name="query",
     *     in="query",
     *     description="Case-insensitive search term. Use * at the beginning and/or end to truncate.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="parent",
     *     in="query",
     *     description="Only search children of this concept, specified by URI.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="group",
     *     in="query",
     *     description="Only search children of this group, specified by URI.",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="fields",
     *     in="query",
     *     description="Space-separated list of extra fields to include in the results. Supported values: 'broader'",
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="unique",
     *     in="query",
     *     description="Boolean flag to indicate that each concept should be returned only once, instead of returning all the different ways it could match (for example both via prefLabel and altLabel).",
     *     @OA\Schema(
     *       type="boolean",
     *       default=false
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="lang",
     *     in="query",
     *     description="Search language.",
     *     @OA\Schema(
     *       type="string",
     *       enum={"nb", "nn", "en"}
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="labellang",
     *     in="query",
     *     description="Language used to format results.",
     *     @OA\Schema(
     *       type="string",
     *       enum={"nb", "nn", "en"}
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="vocab",
     *     in="query",
     *     description="Subject vocabulary. Leave blank to search all subject vocabularies.",
     *     @OA\Schema(
     *       type="string",
     *       enum={"realfagstermer", "humord", "tekord", "mrtermer", "usvd", "lskjema", "ddc"}
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="type",
     *     in="query",
     *     description="All resources have type `Concept`or `Facet`. Concepts are further subdivided into `Topic`, `Place`, `Time`, `CompoundConcept`, `VirtualCompoundConcept` and `NonIndexable`.",
     *     @OA\Schema(
     *       type="string",
     *       enum={"Concept", "Facet", "Topic", "Place", "Time", "CompoundConcept", "VirtualCompoundConcept", "NonIndexable"}
     *     )
     *   )
     * )
     *
     * @param  Skosmos  $skosmos
     * @param  Request  $request
     * @return Response
     */
    public function search(Skosmos $skosmos, Request $request)
    {
        if (!$request->has('query')) {
            return response()->json([
                'error' => 'empty_query'
            ], 400);
        }

        $data = $skosmos->search($request);

        return response()->json($data);
    }

    /**
     * @OA\Get(
     *   path="/subjects/show/{vocab}/{id}",
     *   summary="Find authority record by ID",
     *   tags={"Authorities"},
     *   @OA\Response(
     *     response=200,
     *     description="success"
     *   ),
     *   @OA\Parameter(
     *     name="vocab",
     *     in="path",
     *     description="Subject vocabulary. Leave blank to search all subject vocabularies.",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       enum={"realfagstermer", "humord", "tekord", "mrtermer", "usvd", "lskjema"}
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Local ID, e.g. `c006445`.",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="expand_mappings",
     *     in="query",
     *     description="If set to false, you'll only get the URIs for mapped concepts. If set to true, you will get data for one level of mappings.",
     *     @OA\Schema(
     *       type="boolean",
     *       default=false
     *     )
     *   )
     * )
     *
     * @param  Skosmos $skosmos
     * @param  Request $request
     * @param          $vocab
     * @param          $id
     * @return Response
     */
    public function show(Skosmos $skosmos, Request $request, $vocab, $id)
    {
        $data = $skosmos->get($vocab, $id, $request->get('expand_mappings'));
        return response()->json($data);
    }

    /**
     * @OA\Get(
     *   path="/subjects/lookup",
     *   summary="Find authority record by index term value",
     *   description="Get a single subject by term value.",
     *   tags={"Authorities"},
     *   @OA\Response(
     *     response=200,
     *     description="success"
     *   ),
     *   @OA\Parameter(
     *     name="vocab",
     *     in="query",
     *     description="Subject vocabulary. Leave blank to search all subject vocabularies.",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       enum={"realfagstermer", "humord", "tekord", "mrtermer", "usvd", "lskjema"}
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="query",
     *     in="query",
     *     description="Term, e.g. `Fisker`.",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="type",
     *     in="query",
     *     description="Example: Set type to ’Place’ if you want the place ‘Java’, ‘Topic’ if you want the programming language. ",
     *     @OA\Schema(
     *       type="string",
     *       enum={"Concept", "Facet", "Topic", "Place", "Time", "CompoundConcept", "VirtualCompoundConcept", "NonIndexable"}
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="expand_mappings",
     *     in="query",
     *     description="If set to false, you'll only get the URIs for mapped concepts. If set to true, you will get data for one level of mappings.",
     *     @OA\Schema(
     *       type="boolean",
     *       default=false
     *     )
     *   )
     * )
     *
     * @param  Skosmos  $skosmos
     * @param  Request  $request
     * @return Response
     */
    public function lookup(Skosmos $skosmos, Request $request)
    {
        if (!$request->has('query')) {
            return response()->json([
                'error' => 'empty_query'
            ], 400);
        }
        $data = $skosmos->search($request);

        if (!count($data->results)) {
            return response()->json([
                'error' => 'subject_not_found'
            ], 404);
        }

        $uri = $data->results[0]->uri;

        $data = $skosmos->getByUri($uri, $request->get('expand_mappings'));

        return response()->json($data);
    }
}
