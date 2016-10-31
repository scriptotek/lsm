<?php

namespace App;

use BCLib\PrimoServices\DeepLink;
use BCLib\PrimoServices\QueryTerm;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;

class PrimoResult
{
    protected $brief;
    protected $full;
    protected $doc;
    protected $deeplinkProvider;

    protected $primoInst;  // E.g. 'UBO'
    protected $almaInst;  // E.g. '47BIBSYS_UBO'

    function __construct(QuiteSimpleXMLElement $doc, DeepLink $deeplinkProvider, $options)
    {
        $this->doc = $doc;
        $this->deeplinkProvider = $deeplinkProvider;
        $this->brief = ['type' => ($this instanceof PrimoRecordGroup) ? 'group' : 'record'];
        $this->full = [];
        $this->primoInst = strtoupper(array_get($options, 'primo_inst', null));
        $this->almaInst = strtoupper(array_get($options, 'alma_inst', null));
    }

    public function toArray($fullRepr=false)
    {
        $data = $this->brief;
        $data['links'] = [
            'self' => $this->link(),
        ];
        if ($fullRepr) {
            $data['links']['primo'] = $this->primoLink();
            if ($this instanceof PrimoRecord) {
	            $data['links']['cover'] = $this->coverLink();
            }
            // $this->addLocations();
        }

        if ($fullRepr) {
            $data = array_merge($data, $this->full);
        }
        return $data;
    }

    public function jsonSerialize()
    {
        return toArray('full');
    }

    protected function extractArray(QuiteSimpleXMLElement $group, $xpath)
    {
        return array_map(function ($x) {
            return $x->text();
        }, $group->all($xpath));
    }

    protected function extractMarcArray(QuiteSimpleXMLElement $group, $xpath)
    {
        $codelist = [
            'S' => 'status',

            'I' => 'institution',
            'L' => 'library',
            '1' => 'collection',
            '2' => 'callcode',

            'X' => 'institutionCode',
            'Y' => 'libraryCode',
            'Z' => 'collectionCode',
            'O' => 'id',
            'U' => 'url',
            'D' => 'description',

        ];
        return array_map(function($ava) use ($codelist) {
            $o = [];

            // FIX for Primo API not returning consistent data..
            if (substr($ava, 0, 1) != '$') {
                $ava = '$$V' . $ava;
            }

            foreach (explode('$$', $ava) as $el) {
                if (strlen($el)) {
                    $code = array_get($codelist, substr($el, 0, 1), substr($el, 0, 1));
                    $o[$code] = trim(substr($el, 1));
                }
            }
            return $o;
        }, $this->extractArray($group, $xpath));
    }

    protected function extractUrls($record, $getits)
    {
        $urls = [];

        // Add urls for Alma-E, Online Resource and Alma-D
        foreach ($this->extractGetIts($getits) as $getit) {
            if (in_array(array_get($getit, 'category'), ['Online Resource', 'Alma-E'])) {
                $urls[$getit['url1']] = 'Available online';
            } elseif (array_get($getit, 'category') == 'Alma-D') {
                if ($record->text('./p:delivery/p:resdelscope') == 'NB_D_DELRES') {
                    $urls[$getit['url1']] = 'Digitized online version only available at the National Library';
                } else {
                    $urls[$getit['url1']] = 'Available online (digitized)';
                }
            }
        }

        // Add link descriptions for online resources
        $links = $this->extractMarcArray($record, './p:links/p:linktorsrc');
        foreach ($links as $link) {
            $urls[$link['url']] = array_get($link, 'description', 'Available online');
        }

        $out = [];
        foreach ($urls as $key => $val) {
            $out[] = ['url' => $key, 'description' => $val];
        }

        return $out;
    }

    protected function extractThumbs($thumbs)
    {
        $out = [];
        foreach ($thumbs as $thumb) {
            if (preg_match('/images.amazon.com/', $thumb)) {
                $out['amazon'] = $thumb;
            } else if (preg_match('/innhold.bibsys.no/', $thumb)) {
                $out['bibsys'] = $thumb;
            }
        }
        return $out;
    }

    protected function extractGetIts($getits)
    {
        $out = [];
        foreach ($getits as $node) {
            $out[] = [
                'category' => $node->attr('deliveryCategory'),
                'url1' => $node->attr('GetIt1'),
                'url2' => $node->attr('GetIt2'),
            ];
        }

        return $out;
    }

    public function process() {
        $record = $this->doc->first('./p:PrimoNMBib/p:record');
        $facets = $record->first('./p:facets');

        $this->brief['title'] = $record->text('./p:display/p:title') ?: null;

        $this->brief['edition'] = $record->text('./p:display/p:edition') ?: null;
        // $this->brief['creator'] = $record->text('./p:display/p:creator') ?: $record->text('./p:display/p:contributor');

        $this->brief['creator_string'] = $record->text('./p:display/p:creator');
        $this->brief['creators'] = $this->extractArray($facets, './p:creatorcontrib');

        $this->brief['date'] = $record->text('./p:display/p:creationdate') ?: null;
        $this->brief['date'] = preg_replace('/[^0-9-]/', '', $this->brief['date']);

        $this->brief['publisher'] = $record->text('./p:display/p:publisher') ?: null;
        $this->full['abstract'] = $record->text('./p:addata/p:abstract') ?: null;

        $this->full['isbns'] = $this->extractArray($record, './p:search/p:isbn');
//        $this->issns = $this->extractArray($record, './p:search/p:issn');
        $this->full['descriptions'] = $this->extractArray($record, './p:display/p:description');

        //$this->brief['material'] = $this->preferredResourceType($this->extractArray($facets, './p:rsrctype'));
        $this->brief['material'] = $this->extractArray($facets, './p:rsrctype');
        $this->full['format'] = $record->text('./p:display/p:type') ?: null;
        $this->full['bib_format'] = $record->text('./p:display/p:format') ?: null;
        $this->full['ispartof'] = $record->text('./p:display/p:ispartof') ?: null;
        $this->full['responsibility'] = $record->text('./p:display/p:lds22') ?: null;

        $this->full['frbr_type'] = $facets->text('./p:frbrtype');
        $this->full['frbr_group_id'] = $facets->text('./p:frbrgroupid');

        // Series stuff:
        $this->full['series'] = $record->text('./p:addata/p:seriestitle') ?: null;
        // $this->relation = $record->text('./p:display/p:relation') ?: null;

        // @TODO get indices from config
        $this->full['subjects']['realfagstermer'] = $this->extractArray($record, './p:search/p:lsr20');
        $this->full['subjects']['humord'] = $this->extractArray($record, './p:search/p:lsr14');
        $this->full['subjects']['tekord'] = $this->extractArray($record, './p:search/p:lsr12');
        $this->full['subjects']['mrtermer'] = $this->extractArray($record, './p:search/p:lsr19');
        $this->full['subjects']['place'] = $this->extractArray($record, './p:search/p:lsr17');
        $this->full['subjects']['topic'] = $this->extractArray($record, './p:search/p:topic');
        $this->full['subjects']['subject'] = $this->extractArray($record, './p:search/p:subject');
        $this->full['subjects']['genre'] = $this->extractArray($record, './p:facets/p:genre');
        $this->full['subjects']['keyword'] = [];

        // Trim ending dots
        $this->full['subjects']['subject'] = array_map(function($s) {
            return trim($s, '.');
        }, $this->full['subjects']['subject']);

        // Filter out free keywords
        $this->full['subjects']['keyword'] = array_values(array_filter($this->full['subjects']['subject'], function($s) {
            $firstChar = mb_substr($s,0, 1);
            return (mb_strtolower($firstChar) === $firstChar);
        }));
        $this->full['subjects']['subject'] = array_values(array_filter($this->full['subjects']['subject'], function($s) {
            $firstChar = mb_substr($s,0, 1);
            return (mb_strtolower($firstChar) !== $firstChar);
        }));


        $getits = $this->doc->all('./sear:GETIT');
        $sear_links = $this->doc->first('./sear:LINKS');

        $this->full['urls'] = $this->extractUrls($record, $getits);
        $this->full['thumbnails'] = $this->extractThumbs($this->extractArray($sear_links, './s:thumbnail'));

        return $this;
    }
}
