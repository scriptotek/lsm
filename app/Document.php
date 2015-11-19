<?php

namespace App;

use Swagger\Annotations as SWG;

/**
 * @SWG\Definition()
 */
class Document {

    /**
     * The product name
     * @var string
     * @SWG\Property()
     */
    public $name;

}
