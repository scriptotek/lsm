<?php

namespace App;

use BCLib\PrimoServices\Availability\AlmaClient;
use BCLib\PrimoServices\PrimoException;
use BCLib\PrimoServices\PrimoServices;
use BCLib\PrimoServices\Query;
use BCLib\PrimoServices\QueryTerm;
use Guzzle\Http\Client as HttpClient;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;

class SimpleSearch {

    public $orderedMaterialList = ['e-books', 'print-books'];

    public $primo;
    public $alma;
    public $indices;

    public function __construct(PrimoServices $primo, AlmaClient $alma)
    {
        $this->primo = $primo;
        $this->alma = $alma;
        $this->indices = config('app.primo.indices');
    }

    protected function prepareQuery($options)
    {
        $institution = $options->get('institution', config('app.primo.institution'));
        $scope = $options->get('scope', config('app.primo.default_scope'));
        $queryObj = new Query($institution);
        $queryObj->local($scope);
        $queryObj->onCampus(true);

        return $queryObj;
    }

    // public function freeTextSearch($query, $options=[])
    // {
    //     $queryObj = $this->prepareQuery($options);
    //     $material = array_get('options', 'material');

    //     $queryTerm = new QueryTerm();
    //     $queryTerm->set('any', QueryTerm::CONTAINS, $query);
    //     $queryObj->addterm($queryTerm);

    //     // $queryTerm2 = new QueryTerm();
    //     // $queryTerm2->set('facet_library', QueryTerm::CONTAINS, 'ureal');  // Dekker INF også???
    //     // $queryObj->addterm($queryTerm2);

    //     if (in_array($material, $this->orderedMaterialList)) {
    //         $queryTerm3 = new QueryTerm();
    //         $queryTerm3->set('facet_rtype', QueryTerm::EXACT, $material);
    //         $queryObj->addterm($queryTerm3);
    //     }

    //     return $this->processQuery($queryObj, $options);
    // }

    public function subjectSearch($subject, $vocabulary, $options=[])
    {
        $queryObj = $this->prepareQuery($options);

        $queryTerm = new QueryTerm();
        if (array_key_exists($vocabulary, $this->indices)) {
            $index = 'lsr' . $this->indices[$vocabulary];
        } else {
            $index = 'sub';
        }
        $queryTerm->set($index, QueryTerm::EXACT, $term);
        $queryObj->addTerm($queryTerm);

        return $this->processQuery($queryObj, $options);
    }

    protected function preferredResourceType($rtypes)
    {
        foreach ($this->orderedMaterialList as $rtype) {
            if (in_array($rtype, $rtypes)) {
                return $rtype;
            }
        }
        return $rtypes[0];
    }

    /*
    protected function processDocument($document)
    {
        $sources = [];
        foreach ($document->components as $component) {
            $sources[] = [
                'source' => $component->source,
                'source_record_id' => $component->source_record_id,
                // 'availability' => $component->availability,
                // 'alma' => $component->alma_ids,
            ];
        }
     //   return $document;

        if (isset($document->isbn) && count($document->isbn)) {
            $cover = 'https://emnesok.biblionaut.net/?action=cover&isbn=' . $document->isbn[0];
        } else {
            $cover = null; // 'https://emnesok.biblionaut.net/no_cover.jpg';
        }

        $deeplinkProvider = $this->primo->createDeepLink();
        $deeplink = 'http://' . $deeplinkProvider->view('UBO')
            ->link($document->id);

        return array(
            'id' => $document->id,
            // 'description' => $document->abstract ?: $document->description,
            'cover' => $cover, // $document->cover_images,  // ) ? $document->cover_images[0] : null,
            //'fulltext' => $document->fulltext,
            //'openurl' => $document->openurl,
            //'openurl_fulltext' => $document->openurl,
            'title' => $document->title,
            //'type' => $document->type,
            'creators' => $document->creator_facet,
            'subjects' => $document->subjects,
            'material_category' => $this->preferredResourceType($document->resourcetype_facet),
            //'rtypes' => $document->resourcetype_facet,
            'frbr_group' => $document->frbr_group_id,
            'sources' => $sources,
            'date' => preg_replace('/[^0-9]/', '', $document->date),
            'isbns' => $document->isbn,
            'deeplink' => $deeplink,


            // 'components' => $document->components,
            // 'getit' => count($document->getit) ? $document->getit[0]->getit_1 : null,
        );
    }
    */

    /*protected function processFacets($facets)
    {
        $nameMap = [
            // 'creator' => 'creator',
            'rtype' => 'material',
            'local4' => 'location',
            'library' => 'sublocation',
            'local10' => 'dewey',
        ];
        foreach ($this->indices as $key => $value) {
            $nameMap['local' . $value] = $key;
        }

        $out = [];
        foreach ($facets as $facet) {
            if (isset($nameMap[$facet->name])) {
                $out[$nameMap[$facet->name]] = $facet->values;
            }
        }
        return $out;
    }*/

    /*
    protected function processQuery(Query $queryObj, $options)
    {
        $start = $options->get('start', 1);
        $limit = $options->get('limit', 10);
        $sort = $options->get('sort', 'popularity');

        $queryObj->sortField($sort)
            ->start($start)
            ->bulkSize($limit);

        $response = new \stdClass();
        $response->primo_url = $this->primo->url('brief', $queryObj);
        $response->total_results = 0;
        $response->error = null;

        $t0 = microtime(true);
        try {
            $rs = $this->primo->search($queryObj);
        } catch (PrimoException $e) {
            $response->error = $e->getMessage();
            return $response;
        }
        $time_spent = microtime(true) - $t0;

        $records = $rs->results;

        // $records = $this->alma->checkAvailability($records);

        $response->docs = array_map(array(&$this, 'processDocument'), $records);
        $response->facets = $this->processFacets($rs->facets);
        $response->total_results = intval($rs->total_results);
        $response->time_spent = round($time_spent * 100) / 100;

        return $response;
    }*/

    protected function processQuery(Query $queryObj, $options, $expanded=false)
    {
        $start = $options->get('start', 1);
        $limit = $options->get('limit', 10);
        $sort = $options->get('sort', 'popularity');

        $queryObj->sortField($sort)
            ->start($start)
            ->bulkSize($limit);

        $url = str_replace('json=true&', '', $this->primo->url('brief', $queryObj));

        $response = new \stdClass();
        $response->primo_url = $url;
        $response->total_results = 0;
        $response->error = null;

        $client = new HttpClient();
        $request = $client->get($url);
        $body = $request->send()->getBody();

        $root = new QuiteSimpleXMLElement(strval($body));
        $root->registerXPathNamespace('s', 'http://www.exlibrisgroup.com/xsd/jaguar/search');
        $root->registerXPathNamespace('p', 'http://www.exlibrisgroup.com/xsd/primo/primo_nm_bib');

        $deeplinkProvider = $this->primo->createDeepLink();

        $out = [];
        foreach ($root->xpath('//s:DOC') as $doc) {
            $out[] = (new PnxDocument($doc, $deeplinkProvider))->process()->toArray($expanded);
        }

        $docset = $root->first('//s:DOCSET');

        $hits = intval($docset->attr('TOTALHITS'));
        $first = intval($docset->attr('FIRSTHIT'));
        $next = intval($docset->attr('LASTHIT')) + 1;
        if ($next >= $hits) {
            $next = null;
        }
        return [
            'source' => $url,
            'error' => null,
            'first' => $first,
            'next' => $next,
            'total_results' => $hits,
            'documents' => $out,
        ];
    }

    public function search($input)
    {
        $queryObj = $this->prepareQuery($input);

        if ($input->has('query')) {
            $queryTerm = new QueryTerm();
            $queryTerm->set('any', QueryTerm::CONTAINS, $input->get('query'));
            $queryObj->addterm($queryTerm);
        }

        if ($input->has('subject')) {
            $vocabulary = $input->get('vocabulary');
            $queryTerm = new QueryTerm();
            $index = isset($this->indices[$vocabulary]) ? 'lsr' . $this->indices[$vocabulary] : 'sub';
            $queryTerm->set($index, QueryTerm::EXACT, $input->get('subject'));
            $queryObj->addTerm($queryTerm);
        }

        // if ($input->has('subject')) {
        //     $vocabulary = $input->get('vocabulary');
        //     $queryTerm = new QueryTerm();
        //     $index = isset($this->indices[$vocabulary]) ? 'lsr' . $this->indices[$vocabulary] : 'sub';
        //     $queryTerm->set($index, QueryTerm::EXACT, $input->get('subject'));
        //     $queryObj->addTerm($queryTerm);
        // }

        if ($input->has('library')) {
            $library = $input->get('library');
            $queryTerm = new QueryTerm();

            // @TODO: Move to config
            $index = 'lsr04';

            $queryTerm->set($index, QueryTerm::CONTAINS, $input->get('library'));
            $queryObj->addTerm($queryTerm);
        }

        if ($input->has('format')) {
            $queryTerm = new QueryTerm();
            $queryTerm->set('facet_rtype', QueryTerm::EXACT, $input->get('format'));
            $queryObj->addterm($queryTerm);
        }

        return $this->processQuery($queryObj, $input);
    }

    public function lookupDocument($docId, $options=[])
    {
        $institution = $options->get('institution', config('app.primo.institution'));
        $scope = $options->get('scope', config('app.primo.default_scope'));
        $queryObj = new Query($institution);
        $queryObj->local($scope);
        $queryObj->onCampus(true);

        $url = str_replace('json=true&', '', $this->primo->url('full', $queryObj));
        $url = str_replace('&indx=1&bulkSize=10', '', $url);
        $url .= '&docId=' . $docId . '&getDelivery=true';

        $client = new HttpClient();
        $request = $client->get($url);
        $body = $request->send()->getBody();

        $root = new QuiteSimpleXMLElement(strval($body));
        $root->registerXPathNamespace('s', 'http://www.exlibrisgroup.com/xsd/jaguar/search');
        $root->registerXPathNamespace('p', 'http://www.exlibrisgroup.com/xsd/primo/primo_nm_bib');

        $deeplinkProvider = $this->primo->createDeepLink();

        $doc = $root->first('//s:DOC');
        $out = (new PnxDocument($doc, $deeplinkProvider))->process()->toArray('full');

        return [
            'meta' => [
                'source' => $url,
            ],
            'error' => null,
            'document' => $out,
        ];

    }

}
