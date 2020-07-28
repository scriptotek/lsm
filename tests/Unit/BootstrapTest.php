<?php

namespace Tests\Unit;

use Tests\TestCase;

class BootstrapTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testMbEncoding()
    {
        $this->assertEquals(mb_internal_encoding(), 'UTF-8');
    }
}
