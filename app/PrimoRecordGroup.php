<?php

namespace App;

use BCLib\PrimoServices\DeepLink;
use BCLib\PrimoServices\QueryTerm;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;

class PrimoRecordGroup extends PrimoResult implements \JsonSerializable
{

    public function __get($property)
    {
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
        // Very brittle temporary fix for
        // https://github.com/scriptotek/emnesok/issues/119
        // since Ex Libris doesn't respond :(

        $someRecordId = $this->doc->text('./p:PrimoNMBib/p:record/p:control/p:recordid');

        return "{$this->primoHost}/primo-explore/search?vid={$this->primoView}&query=any,contains,{$this->id}&facet=frbrgroupid,include,{$this->id}";


        return "https://bibsys-almaprimo.hosted.exlibrisgroup.com/primo_library/libweb/action/search.do?cs=frb&ct=frb&frbg={$this->id}&fctN=facet_frbrgroupid&fctV={$this->id}&doc={$someRecordId}&rfnGrp=frbr&frbrSrt=date&frbrRecordsSource=Primo+Local&frbrSourceidDisplay=BIBSYS_ILS&query=facet_frbrgroupid%2Cexact%2C{$this->id}&fn=search&search_scope=bibsys_ils&dscnt=0&scp.scps=scope%3A(BIBSYS_ILS)&vid=UBO&ct=search&institution=UBO&tab=library_catalogue&vl(freeText0)=Utgaver";

        // $queryTerm = new QueryTerm();
        // $queryTerm->set('facet_frbrgroupid', QueryTerm::EXACT, $this->id);

        // return $this->deeplinkProvider
        //     ->view($this->primoInst)
        //     ->search($queryTerm, 'bibsys_ils', 'library_catalogue');
    }

    public function process()
    {
        parent::process();

        $this->brief['id'] = $this->doc->text('./p:PrimoNMBib/p:record/p:facets/p:frbrgroupid');

        return $this;
    }
}
