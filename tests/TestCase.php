<?php

class TestCase extends Laravel\Lumen\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    /**
	 * Assert that two arrays are equal. This helper method will sort the two arrays before comparing them if
	 * necessary. This only works for one-dimensional arrays, if you need multi-dimension support, you will
	 * have to iterate through the dimensions yourself.
	 * @param array $expected the expected array
	 * @param array $actual the actual array
	 */
	protected function assertSetsEqual(array $expected, array $actual) {
	    // check length first
	    // $this->assertEquals(count($expected), count($actual), 'Failed to assert that two arrays have the same length.');

        $this->assertTrue(sort($expected), 'Failed to sort array.');
        $this->assertTrue(sort($actual), 'Failed to sort array.');

	    $this->assertEquals($expected, $actual);
	}
}
