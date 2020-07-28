<?php

namespace App;

use BCLib\PrimoServices\PrimoServices;
use BCLib\PrimoServices\Query;
use BCLib\PrimoServices\QueryTerm;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use Illuminate\Support\Arr;

class PrimoSearch
{

    public $orderedMaterialList = ['e-books', 'print-books'];

    public $primo;
    public $alma;
    public $indices;

    // Error codes that are not really errors, but rather informational messages or warnings.
    public $ignoreErrors = [
        // Controlled Vocabulary Expansion
        'controlled.vocabulary',
        'controlled.voc.synonym',

        // Search expansion
        'search.message.ui.expansion.pc',
        'search.message.ui.expansion',

        // Too many wildcards, search results might be incomplete
        'search.error.wildcards.toomanyclauses',
    ];

    public function __construct(PrimoServices $primo, HttpClient $http, MessageFactory $messageFactory)
    {
        $this->primo = $primo;
        $this->http = $http;
        $this->messageFactory = $messageFactory;
        $this->indices = config('primo.indices');
    }

    protected function newQuery($options)
    {
        $opts = $this->getRecordOptions($options);
        $queryObj = new Query($opts['primo_inst']);
        $queryObj->local($opts['primo_scope']);
        $queryObj->onCampus(true);

        if (Arr::has($options, 'institution')) {
            $queryTerm = new QueryTerm();
            $queryTerm->set('facet_local4', QueryTerm::EXACT, Arr::get($options, 'institution'));
            $queryObj->addTerm($queryTerm);
        }

        if (Arr::has($options, 'library')) {
            $library = explode(',', Arr::get($options, 'library'));
            $queryTerm = new QueryTerm();
            $queryTerm->set('facet_library', QueryTerm::EXACT, $library);
            $queryObj->includeTerm($queryTerm);
        }

        if (Arr::has($options, 'material')) {
            $queryTerm = new QueryTerm();

            /**
             * This is really weird, but using
             *
             *    $queryTerm->set('rtype',
             *       QueryTerm::EXACT,
             *       explode(',', Arr::get($options, 'material'))
             *    );
             *
             * sometimes resulted in fewer results than expected. E.g. for the
             * search "field theories Eduardo" with "rtype,exact,print-books",
             * I got 3 results rather than the expected 4. Using "facet_rtype"
             * seems to work better.
             */
            $queryTerm->set(
                'facet_rtype',
                QueryTerm::EXACT,
                explode(',', Arr::get($options, 'material'))
            );

            $queryObj->includeTerm($queryTerm);
        }

        $start = Arr::get($options, 'start', 1);
        $limit = Arr::get($options, 'limit', 10);
        $sort = Arr::get($options, 'sort', 'relevance');
        if ($sort != 'relevance') {
            $queryObj->sortField($sort);
        }

        $queryObj->start($start)
            ->bulkSize($limit);

        return $queryObj;
    }

    public function getRecordOptions($options)
    {
        $institutions = config('primo.institutions');
        $inst = Arr::get($options, 'institution', config('primo.institution'));
        return [
            'primo_host' => Arr::get($options, 'host', config('primo.host')),
            'primo_inst' => $inst,
            'primo_view' => Arr::get($institutions, "{$inst}.view", $inst),
            'primo_scope' => Arr::get($options, 'scope', config('primo.scope')),
            'alma_inst' => Arr::get($options, 'alma', config('alma.institution')),
        ];
    }

    public function parseFacet($root, $name)
    {
        $values = [];
        foreach ($root->xpath('//s:FACET[@NAME="' . $name . '"]/s:FACET_VALUES') as $value) {
            $values[] = ['value' => $value->attr('KEY'), 'count' => intval($value->attr('VALUE'))];
        }
        $values = array_reverse(array_sort($values, function ($value) {
            return $value['count'];
        }));
        return array_slice($values, 0, 10);
    }

    protected function processQuery(Query $queryObj, $expanded, $fullRepr, $options)
    {
        $url = str_replace('json=true&', '', $this->primo->url('brief', $queryObj));

        if (!count($queryObj->getTerms())) {
            throw new PrimoException('No query given', 0, null, $url);
        }

        $request = $this->messageFactory->createRequest('GET', $url);
        $body = (string) $this->http->sendRequest($request)->getBody();

        if (Arr::get($options, 'raw') == 'true') {
            return $body;
        }

        $root = new QuiteSimpleXMLElement($body);
        $root->registerXPathNamespace('s', 'http://www.exlibrisgroup.com/xsd/jaguar/search');
        $root->registerXPathNamespace('p', 'http://www.exlibrisgroup.com/xsd/primo/primo_nm_bib');

        $error = $root->first('/s:SEGMENTS/s:JAGROOT/s:RESULT/s:ERROR');

        // Ignore errors that are not really errors, caused by Controlled Vocabulary Expansion.
        if ($error && !in_array($error->attr('CODE'), $this->ignoreErrors)) {
            throw new PrimoException($error->attr('MESSAGE'), 0, null, $url);
        }

        $deeplinkProvider = $this->primo->createDeepLink();

        $out = [];
        $opts = $this->getRecordOptions($options);
        foreach ($root->xpath('//s:DOC') as $doc) {
            $out[] = PrimoRecord::make($doc, $deeplinkProvider, $expanded, $opts)->toArray($fullRepr);
        }

        $facets = [];
        $vocab = Arr::get($options, 'vocabulary');
        if (isset($this->indices[$vocab])) {
            $facets[$vocab] = $this->parseFacet($root, 'local' . $this->indices[$vocab]);
        }
        $docset = $root->first('//s:DOCSET');

        $hits = intval($docset->attr('TOTALHITS'));
        $first = intval($docset->attr('FIRSTHIT'));
        $next = intval($docset->attr('LASTHIT')) + 1;
        if ($next > $hits) {
            $next = null;
        }

        if (Arr::get($options, 'expand_groups')) {
            foreach ($out as &$o) {
                if ($o['type'] == 'group') {
                    $o['records'] = Arr::get($this->getGroup($o['id'], []), 'result.records');
                }
            }
        }

        return [
            'source' => $url,
            'first' => $first,
            'next' => $next,
            'total_results' => $hits,
            'results' => $out,
            'facets' => $facets,
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

        if ($input->has('raw_query')) {
            foreach (preg_split('/and/i', $input->get('raw_query')) as $q) {
                $q = explode(',', $q);
                $queryTerm = new QueryTerm();
                $queryTerm->set(trim($q[0]), trim($q[1]), trim($q[2]));
                $queryObj->addterm($queryTerm);
            }
        }

        if ($input->has('subject')) {
            $vocabulary = $input->get('vocabulary');
            foreach (explode(' AND ', $input->get('subject')) as $elem) {
                $queryTerm = new QueryTerm();
                $index = isset($this->indices[$vocabulary]) ? 'lsr' . $this->indices[$vocabulary] : 'sub';
                $queryTerm->set($index, QueryTerm::EXACT, explode(' OR ', $elem));
                $queryObj->includeTerm($queryTerm);
            }
        }

        if ($input->has('genre')) {
            foreach (explode(' AND ', $input->get('genre')) as $elem) {
                $queryTerm = new QueryTerm();
                $queryTerm->set('facet_genre', QueryTerm::EXACT, explode(' OR ', $elem));
                $queryObj->includeTerm($queryTerm);
            }
        }

        if ($input->has('place')) {
            foreach (explode(' AND ', $input->get('place')) as $elem) {
                $queryTerm = new QueryTerm();
                $queryTerm->set('facet_local' . $this->indices['geo'], QueryTerm::EXACT, explode(' OR ', $elem));
                $queryObj->includeTerm($queryTerm);
            }
        }

        $fullRepr = $input->get('repr') == 'full';

        return $this->processQuery($queryObj, false, $fullRepr, $input);
    }

    public function getGroup($groupId, $options = [])
    {
        // Get all results to avoid pagination
        $options['limit'] = 50;

        // Sort by date
        $options['sort'] = 'date';

        $queryObj = $this->newQuery($options);

        $queryTerm = new QueryTerm();
        $queryTerm->set('facet_frbrgroupid', QueryTerm::EXACT, $groupId);
        $queryObj->addTerm($queryTerm);

        $res = $this->processQuery($queryObj, true, true, $options);
        if (Arr::get($options, 'raw') == 'true') {
            return $res;
        }
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

    public function getRecord($docId, $options)
    {
        $opts = $this->getRecordOptions($options);
        $queryObj = new Query($opts['primo_inst']);
        $queryObj->local($opts['primo_scope']);
        $queryObj->onCampus(true);

        $url = str_replace('json=true&', '', $this->primo->url('full', $queryObj));
        $url = str_replace('&indx=1&bulkSize=10', '', $url);
        $url .= '&docId=' . $docId . '&getDelivery=true';

        $request = $this->messageFactory->createRequest('GET', $url);
        $body = (string) $this->http->sendRequest($request)->getBody();

        if (Arr::get($options, 'raw') == 'true') {
            return $body;
        }

        $root = new QuiteSimpleXMLElement($body);
        $root->registerXPathNamespace('s', 'http://www.exlibrisgroup.com/xsd/jaguar/search');
        $root->registerXPathNamespace('p', 'http://www.exlibrisgroup.com/xsd/primo/primo_nm_bib');

        $deeplinkProvider = $this->primo->createDeepLink();

        $error = $root->first('/s:SEGMENTS/s:JAGROOT/s:RESULT/s:ERROR');
        if ($error) {
            throw new PrimoException($error->attr('MESSAGE'), 0, null, $url);
        }

        $doc = $root->first('//s:DOC');
        if (!$doc) {
            throw new PrimoException('Invalid response from Primo', 0, null, $url);
        }
        $out = PrimoRecord::make($doc, $deeplinkProvider, true, $opts)->toArray('full');

        return [
            'source' => $url,
            'error' => null,
            'result' => $out,
        ];
    }
}
