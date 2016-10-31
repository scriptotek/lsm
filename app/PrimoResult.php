<?php

namespace App;

use BCLib\PrimoServices\DeepLink;
use BCLib\PrimoServices\QueryTerm;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;

class PrimoResult
{

    public function toArray($fullRepr=false)
    {
        $data = $this->brief;
        $data['links'] = [
            'self' => $this->link(),
        ];
        if ($fullRepr) {
            $data['links']['primo'] = $this->primoLink();
            if ($this instanceof PrimoRecord) {
	            $data['links']['group'] = $this->groupLink();
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
