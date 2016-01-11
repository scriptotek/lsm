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

final class JsonLdHelper {

    /**
     * This method gives the same output as if we could set '@container' to
     * both '@language' and '@set'.
     * See <https://github.com/json-ld/json-ld.org/issues/407>
     */
    static public function toLanguageMapSet(&$graph, $property)
    {
        if (!isset($graph->{$property})) {
            $graph->{$property} = (object)[];
        } else {
            foreach ($graph->{$property} as $lang => $items) {
                if (gettype($items) == 'string') {
                    $graph->{$property}->{$lang} = [$items];
                }
            }
        }
    }
}
