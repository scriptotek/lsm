<?php

namespace App;

use Scriptotek\Alma\Bibs\Bib;

class AlmaRecord implements \JsonSerializable
{
    protected $bib;

    public function __construct(Bib $bib)
    {
        $this->bib = $bib;
    }

    protected function getHoldings()
    {
        $avaMap = [
            '8' => 'id',
            'a' => 'institution',
            'b' => 'library',
            'q' => 'library_name',
            'c' => 'location',
            'd' => 'callcode',
            'x' => 'public_note',
            'e' => 'availability',  // available, unavailable, or check_holdings
            'f' => 'total_items',
            'g' => 'unavailable_items',
        ];

        $out = [];
        foreach ($this->bib->record->query('AVA') as $field) {
            $out[] = $field->mapSubFields($avaMap);
        }
        return $out;
    }

    protected function getPortfolios()
    {
        $aveMap = [
            '8' => 'id',
            'e' => 'activation',
            'c' => 'collection_id',
            'm' => 'collection_name',
            'n' => 'public_note',
            't' => 'interface_name',
            's' => 'coverage',
            'u' => 'service_url',
        ];

        $out = [];
        foreach ($this->bib->record->query('AVE') as $field) {
            $out[] = $field->mapSubFields($aveMap);
        }
        return $out;
    }

    protected function getRepresentations()
    {
        $avdMap = [
            'b' => 'id',
            'e' => 'label',
            'd' => 'repository_name',
            'f' => 'public_note',
            'h' => 'full_text_link',
        ];

        $out = [];
        foreach ($this->bib->record->query('AVD') as $field) {
            $out[] = $field->mapSubFields($avdMap);
        }
        return $out;
    }

    protected function getCover()
    {
        foreach ($this->bib->record->getFields('856') as $field) {
            if (in_array($field->sf('3'), ['Cover image', 'Omslagsbilde'])) {
                $cover_image = $field->sf('u');

                // Silly hack to get larger images from Bibsys:
                $cover_image = str_replace('mini', 'stor', $cover_image);
                $cover_image = str_replace('LITE', 'STOR', $cover_image);

                return $cover_image;
            }
        }
    }

    protected function getDescription()
    {
        foreach ($this->bib->record->getFields('856') as $field) {
            if (in_array($field->sf('3'), ['Beskrivelse fra forlaget (kort)', 'Beskrivelse fra forlaget (lang)'])) {
                return $field->sf('u');
            }
        }
    }

    protected function getFulltextLink()
    {
        foreach ($this->bib->record->getFields('856') as $field) {
            if (in_array($field->sf('3'), ['Fulltekst'])) {
                return $field->sf('u');
            }
        }
    }

    protected function toArray($includeHoldings = false)
    {
        $out = $this->bib->record->jsonSerialize();

        // echo (string) $this->bib->record;die;

        foreach ($this->bib->record->query('035$a') as $subfield) {
            preg_match('/^\((.+)\)(.+)$/', $subfield->getData(), $matches);
            if ($matches && $matches[1] == 'EXLNZ-47BIBSYS_NETWORK') {
                $out['nz_id'] = $matches[2];
            }
        }

        $out['link'] = route('alma.get', ['id' => $this->bib->record->id]);
        // $out['marc'] = (string) $this->bib->record;

        $out['cover'] = $this->getCover();
        $out['description'] = $this->getDescription();
        $out['fulltext'] = $this->getFulltextLink();

        if ($includeHoldings) {
            $out['holdings'] = $this->getHoldings();
            $out['portfolios'] = $this->getPortfolios();
            $out['representations'] = $this->getRepresentations();
        }

        return $out;
    }

    public function jsonSerialize()
    {
        return $this->toArray(true);
    }
}
