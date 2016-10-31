<?php

namespace App;

use BCLib\PrimoServices\DeepLink;
use BCLib\PrimoServices\QueryTerm;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;

class PrimoRecordGroup extends PrimoResult implements \JsonSerializable
{

    public function __get($property) {
        return isset($this->brief[$property]) ? $this->brief[$property] : (isset($this->full[$property]) ? $this->full[$property] : null);
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

    public function primoLink()
    {
        $queryTerm = new QueryTerm();
        $queryTerm->set('facet_frbrgroupid', QueryTerm::EXACT, $this->id);

        return 'http://' . $this->deeplinkProvider
            ->view($this->primoInst)
            ->search($queryTerm, 'bibsys_ils', 'library_catalogue');
    }

    public function process()
    {
        parent::process();

        $this->brief['id'] = $this->doc->text('./p:PrimoNMBib/p:record/p:facets/p:frbrgroupid');

        return $this;
    }

}
