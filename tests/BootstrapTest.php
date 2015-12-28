<?php

class BootstrapTest extends TestCase
{
    public function testMbEncoding()
    {
        $this->assertEquals(mb_internal_encoding(), 'UTF-8');
    }

    public function testStartpageLoads()
    {
        $this->visit('/')
             ->see('SwaggerUI');
    }
}
