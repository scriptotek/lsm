<?php

namespace App\Http\Controllers;

use App\AlmaRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Scriptotek\Alma\Bibs\Bib;
use Scriptotek\Alma\Client as AlmaClient;
use Scriptotek\Sru\Exceptions\SruErrorException;

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
     *   ),
     *   @OA\Parameter(
     *     name="expand_items",
     *     in="query",
     *     description="Set to true to return information about all holding items and representation files.",
     *     @OA\Schema(
     *       type="boolean",
     *       default=false
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="nz",
     *     in="query",
     *     description="Set to true to search network zone.",
     *     @OA\Schema(
     *       type="boolean",
     *       default=false
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
        $nz = ($request->get('nz') && substr($request->get('nz'), 0, 1) !== 'f');
        $expand = ($request->get('expand_items') && substr($request->get('expand_items'), 0, 1) !== 'f');

        $t0 = microtime(true);
        if ($nz) {
            $alma = $alma->nz;
        }
        try {
            $response = $alma->sru->search($cql, $start, $limit);
        } catch (SruErrorException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
                'url' => $ex->uri,
            ], 400);
        }
        $t1 = microtime(true);

        $results = [];
        foreach ($response->records as $record) {
            $bib = Bib::fromSruRecord($record, $alma);
            $results[] = $this->serializeBibRecord($bib, $expand);
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
     *   ),
     *   @OA\Parameter(
     *     name="expand_items",
     *     in="query",
     *     description="Set to true to return information about all holding items and representation files.",
     *     @OA\Schema(
     *       type="boolean",
     *       default=false
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="nz",
     *     in="query",
     *     description="Set to true to use network zone.",
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
    public function bib(AlmaClient $alma, Request $request, $id)
    {
        $t0 = microtime(true);
        $nz = ($request->get('nz') && substr($request->get('nz'), 0, 1) !== 'f');
        if ($nz) {
            $alma = $alma->nz;
        }
        if (strlen($id) >= 18) {
            $bib_data = $alma->getXML("/bibs/${id}", [
                'expand' => 'p_avail,e_avail,d_avail'
            ]);
            $bib = (new Bib($alma, $id))->setMarcRecord($bib_data->asXML());
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

        if ($request->get('raw') && substr($request->get('raw'), 0, 1) !== 'f') {
            return response($bib->record->toXML(), Response::HTTP_OK)
                ->header('Content-Type', 'application/xml');
        }

        $expand = ($request->get('expand_items') && substr($request->get('expand_items'), 0, 1) !== 'f');
        $data = $this->serializeBibRecord($bib, $expand);

        $t2 = microtime(true);

        $data['timing'] = [
            $api => $t1 - $t0,
            'processing' => $t2 - $t1,
        ];

        return response()->json($data);
    }

    protected function serializeBibRecord(Bib $bib, bool $expand)
    {
        $data = (new AlmaRecord($bib))->jsonSerialize();
        if ($expand) {
            foreach ($data['holdings'] as &$el) {
                $el['items'] = [];
                if (!isset($el['id'])) {
                    // Temporary location
                    continue;
                }
                foreach ($bib->holdings[$el['id']]->items as $item) {
                    $el['items'][] = $item->item_data;
                }
            }
            foreach ($data['representations'] as &$el) {
                $el['files'] = [];
                foreach ($bib->representations[$el['id']]->files as $file) {
                    $el['files'][] = $file;
                }
            }
        }
        return $data;
    }

    /**
     * @OA\Get(
     *   path="/alma/records/{id}/holdings",
     *   summary="Get list of holding records for a given Bib record.",
     *   description="Get list of holding records for a given Bib record",
     *   tags={"Alma"},
     *   @OA\Response(
     *     response=200,
     *     description="Holdings"
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Alma record ID (MMS ID)",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
     *   )
     * )
     */
    public function holdings(AlmaClient $alma, Request $request, $id)
    {
        $out = [];
        foreach ($alma->bibs[$id]->holdings as $holding) {
            $out[] = $holding;
        }

        return response()->json(['holdings' => $out]);
    }

    /**
     * @OA\Get(
     *   path="/alma/records/{id}/holdings/{holding_id}",
     *   summary="Get list of items for a given holding.",
     *   description="Get list of items for a given holding",
     *   tags={"Alma"},
     *   @OA\Response(
     *     response=200,
     *     description="Items"
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Alma MMS ID",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="holding_id",
     *     in="path",
     *     description="Alma holding ID",
     *     required=true,
     *     @OA\Schema(
     *       type="string"
     *     )
     *   )
     * )
     */
    public function items(AlmaClient $alma, Request $request, $id, $holding_id)
    {
        $out = [];
        foreach ($alma->bibs[$id]->holdings[$holding_id]->items as $item) {
            $out[] = $item->item_data;
        }
        $t1 = microtime(true);

        return response()->json(['items' => $out]);
    }
}
