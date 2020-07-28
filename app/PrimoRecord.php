<?php

namespace App;

use BCLib\PrimoServices\DeepLink;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use Illuminate\Support\Arr;

class PrimoRecord extends PrimoResult implements \JsonSerializable
{
    public $orderedMaterialList = ['e-books', 'print-books'];

    static function make(QuiteSimpleXMLElement $doc, DeepLink $deeplinkProvider, $expanded, $options)
    {
        $is_group = ($doc->text('./p:PrimoNMBib/p:record/p:facets/p:frbrtype') != '6' && $doc->text('./p:PrimoNMBib/p:record/p:display/p:version', '1') != '1');

        if ($is_group && !$expanded) {
            $item = new PrimoRecordGroup($doc, $deeplinkProvider, $options);
        } else {
            $item = new PrimoRecord($doc, $deeplinkProvider, $options);
        }
        return $item->process();
    }

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
        return "{$this->primoHost}/primo-explore/fulldisplay?docid={$this->id}&vid={$this->primoView}";

        // return $this->deeplinkProvider
        //     ->view($this->primoView)
        //     ->link($this->id);
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
            $x = Arr::get($item, 'category') == 'Alma-P' && Arr::get($item, 'alma_id');
            return $carry || $x;
        }, false);
    }

    public function hasElectronic($x)
    {
        return array_reduce($x['components'], function ($carry, $item) {
            $x = in_array(Arr::get($item, 'category'), ['Alma-E', 'Online Resource']) && Arr::get($item, 'alma_id');
            return $carry || $x;
        }, false);
    }

    public function hasDigital($x)
    {
        return array_reduce($x['components'], function ($carry, $item) {
            $x = in_array(Arr::get($item, 'category'), ['Alma-D']) && Arr::get($item, 'alma_id');
            return $carry || $x;
        }, false);
    }

    public function extractComponents($record, $getits)
    {

        // Get components
        $components = array_map(function ($x) use ($record) {
            return [
                'id' => $x['V'],
                'fid' => Arr::get($x, 'id'),
            ];
        }, $this->extractMarcArray($record, './p:control/p:sourcerecordid'));

        // Add delivery
        foreach ($this->extractMarcArray($record, './p:delivery/p:delcategory') as $k) {
            $component =& $this->getComponent($components, Arr::get($k, 'id'));

            // @TODO: Beware: This limitaion means results with no local holdings will end up with no category.
            //        See below.
            //if (Arr::get($k, 'institution', $this->primoInst) == $this->primoInst) {
            Arr::set($component, 'category', $k['V']);
            //}
        }

        // Add Alma holdings IDs.
        $alma_ids = [];
        foreach ($this->extractMarcArray($record, './p:control/p:almaid') as $k) {
            $component =& $this->getComponent($components, Arr::get($k, 'id'));
            list($inst, $id) = explode(':', $k['V']);
            Arr::set($component, 'alma_holdings.' . $inst, $id);
        }

        // Add availability
        $component['holdings'] = [];
        $keys = [];

        foreach ($this->extractMarcArray($record, './p:display/p:availlibrary') as $k) {
            $component =& $this->getComponent($components, Arr::get($k, 'id'));

            // @TODO: Beware: This limitaion means some results will end up with on holdings
            // if ($k['institution'] != $this->primoInst) {
            //     continue;
            // }

            $key = Arr::get($k, 'library') . $k['collectionCode'] . Arr::get($k, 'callcode');
            if (in_array($key, $keys)) {
                continue;  // Skip duplicates
            }
            $keys[] = $key;

            $holding = [
                'library' => Arr::get($k, 'library'),
                'collection_name' => $k['collection'],
                'collection_code' => $k['collectionCode'],
                'callcode' => Arr::get($k, 'callcode'),
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
}
