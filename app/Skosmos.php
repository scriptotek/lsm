<?php

namespace App;

use BCLib\PrimoServices\Availability\AlmaClient;
use BCLib\PrimoServices\PrimoServices;
use BCLib\PrimoServices\Query;
use BCLib\PrimoServices\QueryTerm;
use Guzzle\Http\Client as HttpClient;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use Illuminate\Http\Request;
use JsonLdProcessor;

class Skosmos {

    protected $http;
    public $baseUrl;
    protected $context;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
        $this->baseUrl = 'https://data.ub.uio.no/skosmos/rest/v1';

        $this->context = (object)[
            'mads' => 'http://www.loc.gov/mads/rdf/v1#',
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
            'xsd' => 'http://www.w3.org/2001/XMLSchema#',
            'dct' => 'http://purl.org/dc/terms/',
            'ubo' => 'http://data.ub.uio.no/onto#',

            'realfagstermer' => 'http://data.ub.uio.no/realfagstermer/',
            'humord' => 'http://data.ub.uio.no/humord/',
            'ddc' => 'http://data.ub.uio.no/ddc/',
            'tekord' => 'http://data.ub.uio.no/tekord/',
            'mrtermer' => 'http://data.ub.uio.no/mrtermer/',

            // '@language' => 'nb',
            'uri' => '@id',
            'type' => (object)[
                '@id' => '@type',
                '@container' => '@set',
            ],
            'graph' => (object)[
              '@id' => '@graph',
              //'@container' => '@index',
            ],

            'identifier' => (object)[
                '@id' => 'dct:identifier',
                '@language' => null
            ],

            'elementSymbol' => (object)[
                '@id' => 'ubo:elementSymbol',
                '@language' => null,
            ],

            'label' => 'rdfs:label',

            'exactMatch' => (object)[
                '@id' => 'skos:exactMatch',
                '@type' => '@id',
            ],
            'closeMatch' => (object)[
                '@id' => 'skos:closeMatch',
                '@type' => '@id',
            ],
            'broadMatch' => (object)[
                '@id' => 'skos:broadMatch',
                '@type' => '@id',
            ],
            'narrowMatch' => (object)[
                '@id' => 'skos:narrowMatch',
                '@type' => '@id',
            ],
            'relatedMatch' => (object)[
                '@id' => 'skos:relatedMatch',
                '@type' => '@id',
            ],

            'ConceptScheme' => 'skos:ConceptScheme',
            'Concept' => 'skos:Concept',
            'Topic' => 'ubo:Topic',
            'Temporal' => 'ubo:Temporal',
            'Place' => 'ubo:Place',
            'GenreForm' => 'ubo:GenreForm',
            'CompoundConcept' => 'ubo:CompoundConcept',
            'VirtualCompoundConcept' => 'ubo:VirtualCompoundConcept',

            'created' => (object)[
                '@id' => 'dct:created',
                '@type' => 'xsd:dateTime'
            ],
            'modified' => (object)[
                '@id' => 'dct:modified',
                '@type' => 'xsd:dateTime'
            ],

            /* Relationships */
            'inScheme' => (object)[
                '@id' => 'skos:inScheme',
                '@type' => '@id',
            ],
            'broader' => (object)[
                '@id' => 'skos:broader',
                '@type' => '@id',
                '@container' => '@set',
            ],
            'narrower' => (object)[
                '@id' => 'skos:narrower',
                '@type' => '@id',
                '@container' => '@set',
            ],
            'related' => (object)[
                '@id' => 'skos:related',
                '@type' => '@id',
                '@container' => '@set',
            ],
            'components' => (object)[
                '@id' => 'mads:componentList',
                '@type' => '@id',
                '@container' => '@list',
            ],

            /* Language maps */
            'prefLabel' => (object)[
                '@id' => 'skos:prefLabel',
                '@container' => '@language'
            ],
            'altLabel' => (object)[
                '@id' => 'skos:altLabel',
                '@container' => '@language'
            ],
            'definition' => (object)[
                '@id' => 'skos:definition',
                '@container' => '@language'
            ],
            'scopeNote' => (object)[
                '@id' => 'skos:scopeNote',
                '@container' => '@language'
            ],

          ];
    }

    public function request($method, $url, $params)
    {
        $headers = ['Accept' => 'application/json'];
        $url = $this->baseUrl . $url . '?' . http_build_query($params);
        return json_decode($this->http->createRequest($method, $url, $headers)
            ->send()->getBody());
    }

    public function buildQuery(Request $request)
    {
        $query = ['query' => $request->get('query')];

        if ($request->has('labellang')) {
            $query['labellang'] = $request->get('labellang');
        }
        if ($request->has('lang')) {
            $query['lang'] = $request->get('lang');
        }
        if ($request->has('vocab')) {
            $query['vocab'] = $request->get('vocab');
        }

        $typeMap = [
            'Concept' => 'skos:Concept',
            'Facet' => 'isothes:ThesaurusArray',
            'Topic' => 'http://data.ub.uio.no/onto#Topic',
            'Place' => 'http://data.ub.uio.no/onto#Place',
            'Time' => 'http://data.ub.uio.no/onto#Time',
            'CompoundConcept' => 'http://data.ub.uio.no/onto#CompoundConcept',
            'VirtualCompoundConcept' => 'http://data.ub.uio.no/onto#VirtualCompoundConcept',
            'NonIndexable' => 'http://data.ub.uio.no/onto#KnuteTerm',
        ];
        if ($request->has('type') && isset($typeMap[$request->get('type')])) {
            $query['type'] = $typeMap[$request->get('type')];
        }
        if ($request->has('parent')) {
            $query['parent'] = $request->get('parent');
        }
        if ($request->has('group')) {
            $query['group'] = $request->get('group');
        }
        if ($request->has('fields')) {
            $query['fields'] = $request->get('fields');
        }
        $query['unique'] = $request->get('unique', 'true');

        return $query;
    }

    public function search(Request $request)
    {
        $query = $this->buildQuery($request);
        $data = $this->request('GET', '/search', $query);
        return $data;

        // $frame = (object) [
        //   '@context' => $this->context,
        //   '@embed' => '@always',
        //   '@type' => 'skos:Concept',
        // ];

        // $p = new JsonLdProcessor();

        // $options = [];
        // $framed =  $p->compact($data, $frame, $options);

        // return $framed;

    }

    public function getRaw($uri)
    {
        return $this->request('GET', '/data', ['uri' => $uri]);
    }

    public function getByUri($uri, $expandMappings=false)
    {
        $data = $this->getRaw($uri);

        $processor = new JsonLdProcessor();
        $data = $processor->expand($data, []);

        if ($expandMappings) {
            $uris = [];
            foreach ($data as $graph) {
                foreach (['http://www.w3.org/2004/02/skos/core#exactMatch', 'http://www.w3.org/2004/02/skos/core#closeMatch', 'http://www.w3.org/2004/02/skos/core#relatedMatch', 'http://www.w3.org/2004/02/skos/core#narrowMatch', 'http://www.w3.org/2004/02/skos/core#broadMatch'] as $rel) {
                    if (isset($graph->{$rel})) {
                        if (is_array($graph->{$rel})) {
                            foreach ($graph->{$rel} as $uriObj) {
                                $uris[] = $uriObj->{'@id'};
                            }
                        } else {
                            $uris[] = $graph->{$rel}->{'@id'};
                        }
                    }
                }
            }
            foreach ($uris as $mappedUri) {
                $mappedData = $this->getRaw($mappedUri);
                $mappedData = $processor->expand($mappedData, []);
                $data = array_merge($data, $mappedData);
            }
        }

        $processor = new JsonLdProcessor();
        $frame = (object)[
          '@context' => $this->context,
          '@embed' => '@always',
          '@id' => $uri,
        ];

        $framed = $processor->frame($data, $frame, []);

        // Since we only have a single resource, we don't really need @graph
        // Let's make life easier for the client..
        $resource = (object)['@context' => $framed->{'@context'}];
        foreach ($framed->graph[0] as $key => $val) {
            $resource->{$key} = $val;
        }
        JsonLdHelper::toLanguageMapSet($resource, 'altLabel');
        JsonLdHelper::toLanguageMapSet($resource, 'scopeNote');

        // if (isset($framed->graph[0]->exactMatch)) {
        //     $url = $framed->graph[0]->exactMatch;
        //     $shorturl = explode(':', $url);
        //     if (count($shorturl) == 2) {
        //         $moredata = $this->getRaw($this->getUri($shorturl[0], $shorturl[1]));
        //     }
        // }

        return $resource;
    }

    public function getUri($vocab, $id)
    {
        return 'http://data.ub.uio.no/' . $vocab . '/' . $id;
    }

    public function get($vocab, $id, $expandMappings=false)
    {
        $vocab = preg_replace('/[^a-z0-9]/', '', $vocab);
        $id = preg_replace('/[^a-z0-9]/', '', $id);

        return $this->getByUri($this->getUri($vocab, $id), $expandMappings);
    }

}
