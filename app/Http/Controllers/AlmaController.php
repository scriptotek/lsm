<?php

namespace App\Http\Controllers;

use App\AlmaRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Scriptotek\Alma\Bibs\Bib;
use Scriptotek\Alma\Client as AlmaClient;

class AlmaController extends Controller
{
    /**
     * @OA\Get(
     *   path="/alma/search",
     *   summary="Search Alma Bib records using SRU.",
     *   description="Search using SRU. Max 10000 records returned. Pagination: If there's no more results, `next` will be null. Otherwise `next` will hold the value to be used with `first` to get the next batch of results.",
     *   tags={"Alma"},
     *   @OA\Response(
     *     response=200,
     *     description="success"
     *   ),
     *   @OA\Parameter(
     *     name="query",
     *     in="query",
     *     description="CQL query string",
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
     *   )
     * )
     *
     * @param  AlmaClient  $alma
     * @param  Request  $request
     * @return Response
     */
    public function search(AlmaClient $alma, Request $request)
    {
        $cql = $request->query('query');
        $start = $request->query('start', '1');
        $limit = $request->query('limit', '10');
        $results = [];

        $t0 = microtime(true);
        $response = $alma->sru->search($cql, $start, $limit);
        $t1 = microtime(true);
        foreach ($response->records as $record) {
            $results[] = (new AlmaRecord(Bib::fromSruRecord($record, $alma)) )->jsonSerialize();
        }
        $t2 = microtime(true);

        $json = [
            'query' => $cql,
            'timing' => [
                'sru_api' => $t1 - $t0,
                'processing' => $t2 - $t1,
            ],
            'results' => $results,
            'next' => $response->nextRecordPosition,
        ];

        app('db')->insert("INSERT INTO timing (time, action, msecs, data) VALUES (?, ?, ?, ?)", [
            'now',
            'alma_sru_search',
            round(($t1 - $t0) * 1000),
            'query:' . $cql,
        ]);

        return response()->json($json);
    }

    /**
     * @OA\Get(
     *   path="/alma/records/{id}",
     *   summary="Find Alma Bib record by MMS ID.",
     *   description="Get details about a single record",
     *   tags={"Alma"},
     *   @OA\Response(
     *     response=200,
     *     description="An AlmaRecord"
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Alma ID",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="raw",
     *     in="query",
     *     description="Set to true to return the raw MARC21 record.",
     *     @OA\Schema(
     *       type="boolean",
     *       default=false
     *     )
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
        $t0 = microtime(true);
        if (strlen($id) >= 18) {
            $bib_data = $alma->getXML("/bibs/${id}", [
                'expand' => 'p_avail'
            ]);
            $bib = new Bib($alma, $id, null, $bib_data);
            $api = 'alma_bib_request';
        } else {
            $bib = $alma->bibs->search("alma.all_for_ui=$id")->current();
            $api = 'alma_sru_search';
        }
        $t1 = microtime(true);
        app('db')->insert("INSERT INTO timing (time, action, msecs, data) VALUES (?, ?, ?, ?)", [
            'now',
            $api,
            round(($t1 - $t0) * 1000),
            'id:' . $id,
        ]);

        if (is_null($bib)) {
            return response()->json(['error' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        if ($request->get('raw')) {
            return response($bib->record->toXML(), Response::HTTP_OK)
                ->header('Content-Type', 'application/xml');
        }

        $data = (new AlmaRecord($bib))->jsonSerialize();
        $t2 = microtime(true);

        $data['timing'] = [
            $api => $t1 - $t0,
            'processing' => $t2 - $t1,
        ];

        return response()->json($data);
    }
}
