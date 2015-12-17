<?php

namespace App;

use BCLib\PrimoServices\DeepLink;
use BCLib\PrimoServices\QueryTerm;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;

class PrimoRecord implements \JsonSerializable
{
    public $orderedMaterialList = ['e-books', 'print-books'];
    protected $brief;
    protected $full;
    protected $doc;
    protected $deeplinkProvider;
    protected $primoInst;  // E.g. 'UBO'
    protected $almaInst;  // E.g. '47BIBSYS_UBO'

    static function make(QuiteSimpleXMLElement $doc, DeepLink $deeplinkProvider, $expanded=false, $options)
    {
        $is_group = $doc->text('./p:PrimoNMBib/p:record/p:facets/p:frbrtype') != '6';

        if ($is_group && !$expanded) {
            $item = new PrimoRecordGroup($doc, $deeplinkProvider, $options);
        } else {
            $item = new PrimoRecord($doc, $deeplinkProvider, $options);
        }
        return $item->process();
    }

    public function __get($property) {
        return isset($this->brief[$property]) ? $this->brief[$property] : (isset($this->full[$property]) ? $this->full[$property] : null);
    }

    function __construct(QuiteSimpleXMLElement $doc, DeepLink $deeplinkProvider, $options)
    {
        $this->doc = $doc;
        $this->deeplinkProvider = $deeplinkProvider;
        $this->brief = ['type' => 'record'];
        $this->full = [];
        $this->primoInst = strtoupper(array_get($options, 'primo_inst', null));
        $this->almaInst = strtoupper(array_get($options, 'alma_inst', null));
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

    public function process()
    {
        $record = $this->doc->first('./p:PrimoNMBib/p:record');
        $sear_links = $this->doc->first('./sear:LINKS');
        $getits = $this->doc->all('./sear:GETIT');
        $facets = $record->first('./p:facets');

        $this->brief['id'] = $record->text('./p:control/p:recordid');

        $this->brief['title'] = $record->text('./p:display/p:title') ?: null;

        $this->brief['edition'] = $record->text('./p:display/p:edition') ?: null;
        // $this->brief['creator'] = $record->text('./p:display/p:creator') ?: $record->text('./p:display/p:contributor');

        $this->brief['creator_string'] = $record->text('./p:display/p:creator');
        $this->brief['creators'] = $this->extractArray($facets, './p:creatorcontrib');

        $this->brief['date'] = $record->text('./p:display/p:creationdate') ?: null;
        $this->brief['date'] = preg_replace('/[^0-9-]/', '', $this->brief['date']);

        $this->brief['publisher'] = $record->text('./p:addata/p:pub') ?: null;
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

        $this->full['components'] = $this->extractComponents($record, $getits, 'UBO', '47BIBSYS_UBO');

        $this->full['urls'] = $this->extractUrls($record, $getits);

        // @TODO get indices from config
        $this->full['subjects']['realfagstermer'] = $this->extractArray($record, './p:search/p:lsr20');
        $this->full['subjects']['humord'] = $this->extractArray($record, './p:search/p:lsr14');
        $this->full['subjects']['tekord'] = $this->extractArray($record, './p:search/p:lsr12');
        $this->full['subjects']['mrtermer'] = $this->extractArray($record, './p:search/p:lsr19');
        $this->full['subjects']['geo'] = $this->extractArray($record, './p:search/p:lsr17');
        $this->full['subjects']['topic'] = $this->extractArray($record, './p:search/p:topic');
        $this->full['subjects']['subject'] = $this->extractArray($record, './p:search/p:subject');

        $this->full['thumbnails'] = $this->extractThumbs($this->extractArray($sear_links, './s:thumbnail'));

        // Trim ending dots
        $this->full['subjects']['subject'] = array_map(function($s) {
            return trim($s, '.');
        }, $this->full['subjects']['subject']);

        // Filter out free keywords
        $this->full['subjects']['subject'] = array_values(array_filter($this->full['subjects']['subject'], function($s) {
            $firstChar = substr($s,0, 1);
            return (mb_strtolower($firstChar) !== $firstChar);
        }));

        $this->brief['status'] = [
            'print' => $this->hasPrint($this->full),
            'electronic' => $this->hasElectronic($this->full),
        ];

        return $this;
    }

    public function extractUrls($record, $getits)
    {
        $urls = [];

        // Add urls for Alma-E
        foreach ($this->extractGetIts($getits) as $getit) {
            if (in_array(array_get($getit, 'category'), ['Online Resource', 'Alma-E'])) {
                $urls[$getit['url1']] = 'Available online';
            }
        }

        // Add link descriptions for online resources
        $links = $this->extractMarcArray($record, './p:links/p:linktorsrc');
        foreach ($links as $link) {
            $urls[$link['url']] = $link['description'];
        }

        $out = [];
        foreach ($urls as $key => $val) {
            $out[] = ['url' => $key, 'description' => $val];
        }

        return $out;
    }

    public function extractThumbs($thumbs)
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

    function extractGetIts($getits)
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

    public function primoLink()
    {
        if ($this->multiple_editions) {

            $queryTerm = new QueryTerm();
            $queryTerm->set('facet_frbrgroupid', QueryTerm::EXACT, $this->id);

            return 'http://' . $this->deeplinkProvider->view('UBO')
                ->search($queryTerm, null, 'library_catalogue');

        } else {
            return 'http://' . $this->deeplinkProvider->view('UBO')
                ->link($this->id);
        }
    }

    public function link()
    {
        return url('primo/records/' .$this->id);
    }

    public function groupLink()
    {
        return ($this->full['frbr_type'] != '6') ? url('primo/groups/' .$this->full['frbr_group_id']) : null;
    }

    public function coverLink()
    {
        return url('primo/records/' .$this->id . '/cover');
    }

    public function jsonSerialize()
    {
        return toArray('full');
    }

    public function toArray($fullRepr=false)
    {
        $data = $this->brief;
        $data['links'] = [
            'self' => $this->link(),
        ];
        if ($fullRepr) {
            $data['links']['primo'] = $this->primoLink();
            $data['links']['group'] = $this->groupLink();
            $data['links']['cover'] = $this->coverLink();
            // $this->addLocations();
        }

        if ($fullRepr) {
            $data = array_merge($data, $this->full);
        }
        return $data;
    }

    public function & getComponent(&$components, $id)
    {
        if (count($components) == 1) {
            return $components[0];
        }
        foreach ($components as &$component) {
            if ($component['fid'] == $id) {
                return $component;
            }
        }
    }

    public function hasPrint($x)
    {
        return array_reduce($x['components'], function ($carry, $item) {
            $x = array_get($item, 'category') == 'Alma-P' && array_get($item, 'alma_id');
            return $carry || $x;
        }, false);
    }

    public function hasElectronic($x)
    {
        return array_reduce($x['components'], function ($carry, $item) {
            $x = in_array(array_get($item, 'category'), ['Alma-E', 'Online Resource']) && array_get($item, 'alma_id');
            return $carry || $x;
        }, false);
    }

    public function extractComponents($record, $getits)
    {

        // Get components
        $components = array_map(function($x) use ($record){
            return [
                'id' => $x['V'],
                'fid' => array_get($x, 'id'),
            ];
        }, $this->extractMarcArray($record, './p:control/p:sourcerecordid'));

        // Add delivery
        foreach ($this->extractMarcArray($record, './p:delivery/p:delcategory') as $k) {
            $component =& $this->getComponent($components, array_get($k, 'id'));

            // @TODO: Beware: This limitaion means results with no local holdings will end up with no category.
            //        See below.
            //if (array_get($k, 'institution', $this->primoInst) == $this->primoInst) {
            array_set($component, 'category', $k['V']);
            //}
        }

        // Add Alma IDs
        $alma_ids = [];
        foreach ($this->extractMarcArray($record, './p:control/p:almaid') as $k) {
            $component =& $this->getComponent($components, array_get($k, 'id'));
            list($inst, $id) = explode(':', $k['V']);
            array_set($component, 'alma_id.' . $inst, $id);
        }

        // Add availability
        $component['holdings'] = [];
        $keys = [];

        foreach ($this->extractMarcArray($record, './p:display/p:availlibrary') as $k) {
            $component =& $this->getComponent($components, array_get($k, 'id'));

            // @TODO: Beware: This limitaion means some results will end up with on holdings
            // if ($k['institution'] != $this->primoInst) {
            //     continue;
            // }

            $key = array_get($k, 'library') . $k['collectionCode'] . array_get($k, 'callcode');
            if (in_array($key, $keys)) {
                continue;  // Skip duplicates
            }
            $keys[] = $key;

            $holding = [
                'library' => array_get($k, 'library'),
                'collection_name' => $k['collection'],
                'collection_code' => $k['collectionCode'],
                'callcode' => array_get($k, 'callcode'),
                'status' => $k['status'],
                'alma_instance' => $k['institutionCode'],
            ];
            if (isset($alma_ids[$k['institutionCode']])) {
                $holding['alma_id'] = $alma_ids[$k['institutionCode']];
            }
            $component['holdings'][] = $holding;
        }

        return $components;
    }

    private function extractPNXGroups(\stdClass $pnx_record, BibRecord $record)
    {
        $groups = array();
        foreach ($pnx_record as $group_name => $group) {
            if (!is_null($group)) {
                $this->extractGroupFields($group, $group_name, $record);
            }

        }
        return $groups;
    }

    private function extractGroupFields(\stdClass $pnx_group, $group_name, BibRecord $record)
    {
        $fields = array();
        foreach ($pnx_group as $field_name => $field) {
            $record->addField($group_name, $field_name, $field);
        }
        return $fields;
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


    private function extractArray(QuiteSimpleXMLElement $group, $xpath)
    {
        return array_map(function ($x) {
            return $x->text();
        }, $group->all($xpath));
    }

}