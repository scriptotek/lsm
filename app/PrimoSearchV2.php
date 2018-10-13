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

    public function search(array $input)
    {
        return $this->primo->search($input);
    }
}
