<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Scriptotek\Alma\Client as AlmaClient;

class AlmaController extends Controller
{

    /**
     * @SWG\Get(
     *   path="/alma/records/{id}",
     *   description="Get details about a single record",
     *   tags={"Alma"},
     *   produces={"application/json"},
     *   @SWG\Response(
     *     response=200,
     *     description="An AlmaRecord"
     *   ),
     *   @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     description="Alma ID",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="raw",
     *     in="query",
     *     description="Set to true to return the raw MARC21 record.",
     *     type="boolean",
     *     default="false"
     *   )
     * )
     *
     * @param  AlmaClient  $alma
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function getRecord(AlmaClient $alma, Request $request, $id)
    {
        $bib = $alma->bibs->search("alma.all_for_ui=$id")->current();
        if (is_null($bib)) {
            return response()->json(['error' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        if ($request->get('raw')) {
            return response($bib->record->toXML(), Response::HTTP_OK)
                ->header('Content-Type', 'application/xml');
        }

        $json = [
            'id' => $bib->record->id,
            'title' => $bib->record->title,
            'isbns' => $bib->record->isbns,
            'subjects' => $bib->record->subjects,
            // TODO: Add more stuff
        ];

        return response()->json($json);

    }

}
