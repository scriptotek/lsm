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

    static function make(QuiteSimpleXMLElement $doc, DeepLink $deeplinkProvider, $expanded=false)
    {
        $is_group = $doc->text('./p:PrimoNMBib/p:record/p:facets/p:frbrtype') != '6';

        if ($is_group && !$expanded) {
            $item = new PrimoRecordGroup($doc, $deeplinkProvider);
        } else {
            $item = new PrimoRecord($doc, $deeplinkProvider);
        }
        return $item->process();
    }

    public function __get($property) {
        return isset($this->brief[$property]) ? $this->brief[$property] : (isset($this->full[$property]) ? $this->full[$property] : null);
    }

    function __construct(QuiteSimpleXMLElement $doc, DeepLink $deeplinkProvider)
    {
        $this->doc = $doc;
        $this->deeplinkProvider = $deeplinkProvider;
        $this->brief = ['type' => 'record'];
        $this->full = [];
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

        $this->full['frbr_type'] = $facets->text('./p:frbrtype');
        $this->full['frbr_group_id'] = $facets->text('./p:frbrgroupid');

        // Series stuff:
        $this->full['series'] = $record->text('./p:addata/p:seriestitle') ?: null;
        // $this->relation = $record->text('./p:display/p:relation') ?: null;

        $this->full['availability'] = $this->extractMarcArray($record, './p:display/p:availlibrary');

        // @TODO get indices from config
        $this->full['subjects']['realfagstermer'] = $this->extractArray($record, './p:search/p:lsr20');

        return $this;
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
                ->link($this->pnx_id);
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
        }

        if ($fullRepr) {
            $data = array_merge($data, $this->full);
            $data['cover'] = count($data['isbns']) ? 'https://emnesok.biblionaut.net/?action=cover&isbn=' . $data['isbns'][0] : null;
        }
        return $data;
    }


    private function extractGetIts($sear_getit)
    {
        if (!is_array($sear_getit)) {
            $sear_getit = array($sear_getit);
        }

        $result = \array_map(array($this, 'extractGetIt'), $sear_getit);

        return $result;
    }

    private function extractGetIt($sear_getit)
    {
        $getit = new GetIt();
        $getit->getit_1 = $sear_getit->{'@GetIt1'};
        $getit->getit_2 = $sear_getit->{'@GetIt2'};
        $getit->category = $sear_getit->{'@deliveryCategory'};
        return $getit;
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
            '1' => 'collection',
            '2' => 'callcode',
            'S' => 'status',
            'L' => 'library',
            'I' => 'institution',
        ];
        return array_map(function($ava) use ($codelist) {
            $o = [];
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