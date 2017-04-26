<?php

namespace App;

use BCLib\PrimoServices\DeepLink;
use BCLib\PrimoServices\QueryTerm;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;

class PrimoRecord extends PrimoResult implements \JsonSerializable
{
    public $orderedMaterialList = ['e-books', 'print-books'];

    static function make(QuiteSimpleXMLElement $doc, DeepLink $deeplinkProvider, $expanded=false, $options)
    {
        $is_group = ($doc->text('./p:PrimoNMBib/p:record/p:facets/p:frbrtype') != '6' && $doc->text('./p:PrimoNMBib/p:record/p:display/p:version', '1') != '1');

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
        parent::process();

        $record = $this->doc->first('./p:PrimoNMBib/p:record');

        $this->brief['id'] = $record->text('./p:control/p:recordid');

        $this->full['source'] = $record->text('./p:control/p:sourcesystem');
        if ($this->full['source'] == 'Alma') {
            $this->full['alma_id'] = $record->text('./p:control/p:addsrcrecordid');
        }

        $getits = $this->doc->all('./sear:GETIT');
        $this->full['components'] = $this->extractComponents($record, $getits, $this->primoInst, $this->almaInst);

        $this->brief['status'] = [
            'print' => $this->hasPrint($this->full),
            'electronic' => $this->hasElectronic($this->full),
            'digital' => $this->hasDigital($this->full),
        ];

        return $this;
    }

    public function link()
    {
        return url('primo/records/' .$this->id);
    }

    public function primoLink()
    {
        return $this->deeplinkProvider
            ->view($this->primoInst)
            ->link($this->id);
    }

    public function coverLink()
    {
        return url('primo/records/' .$this->id . '/cover');
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

    public function hasDigital($x)
    {
        return array_reduce($x['components'], function ($carry, $item) {
            $x = in_array(array_get($item, 'category'), ['Alma-D']) && array_get($item, 'alma_id');
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

        // Add Alma holdings IDs.
        $alma_ids = [];
        foreach ($this->extractMarcArray($record, './p:control/p:almaid') as $k) {
            $component =& $this->getComponent($components, array_get($k, 'id'));
            list($inst, $id) = explode(':', $k['V']);
            array_set($component, 'alma_holdings.' . $inst, $id);
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


}
