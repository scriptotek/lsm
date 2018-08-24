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
            'a' => 'institution',
            'b' => 'library',
            'c' => 'location',
            'd' => 'callcode',
            'x' => 'public_note',
            'e' => 'availability',
        ];

        $holdings = [];
        foreach ($this->bib->record->query('AVA') as $field) {
            $holdings[] = $field->mapSubFields($avaMap);
        }
        return $holdings;
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
            if (in_array($field->sf('3'), ['Beskrivelse fra forlaget (lang)'])) {
                return $field->sf('u');
            }
        }
        foreach ($this->bib->record->getFields('856') as $field) {
            if (in_array($field->sf('3'), ['Beskrivelse fra forlaget (kort)'])) {
                return $field->sf('u');
            }
        }
    }

    protected function toArray($includeHoldings=false)
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

        if ($includeHoldings) {
            $out['holdings'] = $this->getHoldings();
        }

        return $out;
    }

    public function jsonSerialize()
    {
        return $this->toArray(true);
    }
}
