<?php

namespace App;

class PrimoException extends \Exception
{
	protected $url;

	public function __construct($message = null, $code = 0, \Exception $previous = null, $url = null)
	{
        parent::__construct($message, $code, $previous);
		$this->url = $url;
	}

	public function getUrl()
	{
		return $this->url;
	}

}