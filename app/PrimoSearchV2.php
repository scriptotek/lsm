<?php

namespace App;

use Scriptotek\PrimoSearch\Primo;

class PrimoSearchV2
{

    public $orderedMaterialList = ['e-books', 'print-books'];

    public $primo;
    public $alma;
    public $indices;

    public function __construct(Primo $primo)
    {
        $this->primo = $primo;
        $this->indices = config('primo.indices');
    }

    public function setVid($vid) {
        if (!is_null($vid)) {
            $this->primo->setVid($vid);
        }
    }

    public function search(array $input)
    {
        $res = $this->primo->search($input);

        // Add the source url for debugging
        $res->_source_url = $this->primo->buildSearchUrl($input);

        return $res;
    }

    public function configuration()
    {
        return $this->primo->configuration();
    }

    public function translations($lang)
    {
        return $this->primo->translations($lang);
    }
}
