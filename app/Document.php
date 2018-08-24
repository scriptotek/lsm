<?php

/**
 * @SWG\Swagger(
 *   schemes={"https"},
 *   host="ub-lsm.uio.no",
 *   basePath="/",
 *   @SWG\Info(
 *     title="University of Oslo Library Services Middleware (LSM)",
 *     version="0.1"
 *   )
 * )
 * @SWG\Tag(
 *   name="Documents"
 * )
 */

namespace App;

use Swagger\Annotations as SWG;

/**
 * @SWG\Definition()
 */
class Document
{

    /**
     * The product name
     * @var string
     * @SWG\Property()
     */
    public $name;
}
