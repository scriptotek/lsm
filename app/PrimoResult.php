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
}
