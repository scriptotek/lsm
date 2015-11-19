<?php

namespace App;

use BCLib\PrimoServices\DeepLink;
use BCLib\PrimoServices\QueryTerm;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;

class PrimoRecordGroup implements \JsonSerializable
{
    protected $brief;
    protected $full;
    protected $doc;
    protected $deeplinkProvider;

    public function __get($property) {
        return isset($this->brief[$property]) ? $this->brief[$property] : (isset($this->full[$property]) ? $this->full[$property] : null);
    }

    function __construct(QuiteSimpleXMLElement $doc, DeepLink $deeplinkProvider)
    {
        $this->doc = $doc;
        $this->deeplinkProvider = $deeplinkProvider;
        $this->brief = ['type' => 'group'];
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

    public function link()
    {
        return url('primo/groups/' .$this->id);
    }

    public function process()
    {
        $record = $this->doc->first('./p:PrimoNMBib/p:record');
        $sear_links = $this->doc->first('./sear:LINKS');
        $facets = $record->first('./p:facets');

        $this->brief['id'] = $facets->text('./p:frbrgroupid');

        $this->brief['title'] = $record->text('./p:display/p:title') ?: null;
        $this->brief['creators'] = $this->extractArray($facets, './p:creatorcontrib');

        return $this;
    }

    public function toArray($expanded=false)
    {
        $data = $this->brief;
        $data['links']['self'] = $this->link();
        return $data;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    private function extractArray(QuiteSimpleXMLElement $group, $xpath)
    {
        return array_map(function ($x) {
            return $x->text();
        }, $group->all($xpath));
    }

}