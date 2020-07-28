<?php

namespace Tests\Unit;

use App\PrimoRecord;
use BCLib\PrimoServices\DeepLink;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use Illuminate\Support\Arr;
use Tests\TestCase;

class PrimoRecordTest extends TestCase
{
    public function loadPrimoRecord($filename)
    {
        $dataDir = dirname(dirname(__FILE__)) . '/data/';
        $xml = file_get_contents($dataDir . $filename);

        $root = new QuiteSimpleXMLElement($xml);
        $root->registerXPathNamespace('s', 'http://www.exlibrisgroup.com/xsd/jaguar/search');
        $root->registerXPathNamespace('p', 'http://www.exlibrisgroup.com/xsd/primo/primo_nm_bib');

        $doc = $root->first('//s:DOC');
        $dl = new DeepLink('', '');
        return PrimoRecord::make($doc, $dl, false, [])->toArray(true);
    }

    public function testKeywords()
    {
        $record = $this->loadPrimoRecord('PrimoRecordTest1.xml');

        $this->assertSetsEqual(
            ['engelsk', 'toefl', 'språktester', 'testen', 'språkbruk', 'øvingsbøker'],
            Arr::get($record, 'subjects.keyword')
        );

        $this->assertSetsEqual(
            [],
            Arr::get($record, 'subjects.subject')
        );
    }

    public function testGeographicNames()
    {
        $record = $this->loadPrimoRecord('PrimoRecordGeo.xml');

        $this->assertSetsEqual(
            ['Nordpolen', 'Arktis', 'Russland'],
            Arr::get($record, 'subjects.place')
        );
    }
}
