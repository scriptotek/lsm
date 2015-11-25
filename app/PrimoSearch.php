<?php

namespace App;

use BCLib\PrimoServices\Availability\AlmaClient;
use BCLib\PrimoServices\PrimoServices;
use BCLib\PrimoServices\Query;
use BCLib\PrimoServices\QueryTerm;
use Guzzle\Http\Client as HttpClient;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;

class PrimoSearch {

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

    protected function newQuery($options)
    {
        $institution = $options->get('institution', config('app.primo.institution'));
        $scope = $options->get('scope', config('app.primo.default_scope'));
        $queryObj = new Query($institution);
        $queryObj->local($scope);
        $queryObj->onCampus(true);

        if ($options->has('institution')) {
            $queryTerm = new QueryTerm();
            $queryTerm->set('facet_local4', QueryTerm::EXACT, $options->get('institution'));
            $queryObj->addTerm($queryTerm);
        }

        if ($options->has('library')) {
            $library = explode(',', $options->get('library'));
            $queryTerm = new QueryTerm();
            $queryTerm->set('facet_library', QueryTerm::EXACT, $library);
            $queryObj->includeTerm($queryTerm);
        }

        if ($options->has('material')) {
            $queryTerm = new QueryTerm();
            $queryTerm->set('rtype', QueryTerm::EXACT, explode(',', $options->get('material')));
            $queryObj->includeTerm($queryTerm);
        }

        $start = $options->get('start', 1);
        $limit = $options->get('limit', 10);
        $sort = $options->get('sort', 'popularity');

        $queryObj->sortField($sort)
            ->start($start)
            ->bulkSize($limit);

        return $queryObj;
    }

    protected function processQuery(Query $queryObj, $expanded, $fullRepr)
    {
        $url = str_replace('json=true&', '', $this->primo->url('brief', $queryObj));

        if (!count($queryObj->getTerms())) {
            throw new PrimoException('No query given');
        }

        $client = new HttpClient();
        $request = $client->get($url);
        $body = $request->send()->getBody();

        $root = new QuiteSimpleXMLElement(strval($body));
        $root->registerXPathNamespace('s', 'http://www.exlibrisgroup.com/xsd/jaguar/search');
        $root->registerXPathNamespace('p', 'http://www.exlibrisgroup.com/xsd/primo/primo_nm_bib');

        $error = $root->first('/s:SEGMENTS/s:JAGROOT/s:RESULT/s:ERROR');
        if ($error) {
            throw new PrimoException($error->attr('MESSAGE'), 0, null, $url);
        }

        $deeplinkProvider = $this->primo->createDeepLink();

        $out = [];
        foreach ($root->xpath('//s:DOC') as $doc) {
            $out[] = PrimoRecord::make($doc, $deeplinkProvider, $expanded)->toArray($fullRepr);
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
            'first' => $first,
            'next' => $next,
            'total_results' => $hits,
            'results' => $out,
        ];
    }

    public function search($input)
    {
        $queryObj = $this->newQuery($input);

        if ($input->has('query')) {

            if (strlen($input->get('query')) < 3) {
                throw new PrimoException('Query must be minimum 3 characters long.');
            }

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

        $fullRepr = $input->get('repr') == 'full';

        return $this->processQuery($queryObj, false, $fullRepr);
    }

    public function getGroup($groupId, $options=[])
    {
        // Get all results to avoid pagination
        $options['limit'] = 50;

        // Sort by date
        $options['sort'] = 'date';

        $queryObj = $this->newQuery($options);

        $queryTerm = new QueryTerm();
        $queryTerm->set('facet_frbrgroupid', QueryTerm::EXACT, $groupId);
        $queryObj->addTerm($queryTerm);

        $res = $this->processQuery($queryObj, true, true);
        return [
            'source' => $res['source'],
            'error' => null,
            'result' => [
                'type' => 'group',
                'id' => $groupId,
                'records' => $res['results'],
            ],
        ];
    }

    public function getRecord($docId, $options=[])
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
        $out = PrimoRecord::make($doc, $deeplinkProvider, true)->toArray('full');

        return [
            'source' => $url,
            'error' => null,
            'result' => $out,
        ];

    }

}
