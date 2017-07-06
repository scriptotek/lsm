<?php

namespace App\Http\Controllers;

use App\Skosmos;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubjectsController extends Controller
{

    /**
     * @SWG\Get(
     *   path="/subjects/search",
     *   description="Search for terms, optionally filtered by vocabulary and concept type.",
     *   tags={"Authorities"},
     *   produces={"application/json"},
     *   @SWG\Response(
     *     response=200,
     *     description="success"
     *   ),
     *   @SWG\Parameter(
     *     name="query",
     *     in="query",
     *     description="Case-insensitive search term. Use * at the beginning and/or end to truncate.",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="parent",
     *     in="query",
     *     description="Only search children of this concept, specified by URI.",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="group",
     *     in="query",
     *     description="Only search children of this group, specified by URI.",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="fields",
     *     in="query",
     *     description="Space-separated list of extra fields to include in the results. Supported values: 'broader'",
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="unique",
     *     in="query",
     *     description="Boolean flag to indicate that each concept should be returned only once, instead of returning all the different ways it could match (for example both via prefLabel and altLabel).",
     *     type="boolean",
     *     default="true"
     *   ),
     *   @SWG\Parameter(
     *     name="lang",
     *     in="query",
     *     description="Search language.",
     *     type="string",
     *     enum={"nb", "nn", "en"}
     *   ),
     *   @SWG\Parameter(
     *     name="labellang",
     *     in="query",
     *     description="Language used to format results.",
     *     type="string",
     *     enum={"nb", "nn", "en"}
     *   ),
     *   @SWG\Parameter(
     *     name="vocab",
     *     in="query",
     *     description="Subject vocabulary. Leave blank to search all subject vocabularies.",
     *     type="string",
     *     enum={"realfagstermer", "humord", "tekord", "mrtermer", "usvd", "lskjema"}
     *   ),
     *   @SWG\Parameter(
     *     name="type",
     *     in="query",
     *     description="All resources have type `Concept`or `Facet`. Concepts are further subdivided into `Topic`, `Place`, `Time`, `CompoundConcept`, `VirtualCompoundConcept` and `NonIndexable`.",
     *     type="string",
     *     enum={"Concept", "Facet", "Topic", "Place", "Time", "CompoundConcept", "VirtualCompoundConcept", "NonIndexable"}
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
     * @SWG\Get(
     *   path="/subjects/show/{vocab}/{id}",
     *   description="Get a single subject by ID.",
     *   tags={"Authorities"},
     *   produces={"application/json"},
     *   @SWG\Response(
     *     response=200,
     *     description="success"
     *   ),
     *   @SWG\Parameter(
     *     name="vocab",
     *     in="path",
     *     description="Subject vocabulary. Leave blank to search all subject vocabularies.",
     *     required=true,
     *     type="string",
     *     enum={"realfagstermer", "humord", "tekord", "mrtermer", "usvd", "lskjema"}
     *   ),
     *   @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     description="Local ID, e.g. `c006445`.",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="expand_mappings",
     *     in="query",
     *     description="If set to false, you'll only get the URIs for mapped concepts. If set to true, you will get data for one level of mappings.",
     *     type="boolean",
     *     default="false"
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
     * @SWG\Get(
     *   path="/subjects/lookup",
     *   description="Get a single subject by term value.",
     *   tags={"Authorities"},
     *   produces={"application/json"},
     *   @SWG\Response(
     *     response=200,
     *     description="success"
     *   ),
     *   @SWG\Parameter(
     *     name="vocab",
     *     in="query",
     *     description="Subject vocabulary. Leave blank to search all subject vocabularies.",
     *     required=true,
     *     type="string",
     *     enum={"realfagstermer", "humord", "tekord", "mrtermer", "usvd", "lskjema"}
     *   ),
     *   @SWG\Parameter(
     *     name="query",
     *     in="query",
     *     description="Term, e.g. `Fisker`.",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="type",
     *     in="query",
     *     description="Example: Set type to ’Place’ if you want the place ‘Java’, ‘Topic’ if you want the programming language. ",
     *     type="string",
     *     enum={"Concept", "Facet", "Topic", "Place", "Time", "CompoundConcept", "VirtualCompoundConcept", "NonIndexable"}
     *   ),
     *   @SWG\Parameter(
     *     name="expand_mappings",
     *     in="query",
     *     description="If set to false, you'll only get the URIs for mapped concepts. If set to true, you will get data for one level of mappings.",
     *     type="boolean",
     *     default="false"
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

        if (!count($data->results)){
            return response()->json([
                'error' => 'subject_not_found'
            ], 404);
        }

        $uri = $data->results[0]->uri;

        $data = $skosmos->getByUri($uri, $request->get('expand_mappings'));

        return response()->json($data);
    }


}
