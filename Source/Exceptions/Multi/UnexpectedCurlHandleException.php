<?php
namespace Gazelle\Exceptions\Multi;


use Gazelle\Exceptions\MultiCurlException;


class UnexpectedCurlHandleException extends MultiCurlException
{
	private \CurlHandle $handle;
	
	
	public function __construct(\CurlHandle $handle)
	{
		parent::__construct('Got unexpected \CurlHandle from \MultiCurlHandle');
		$this->handle = $handle;
	}
	
	
	public function getCurlHandle(): \CurlHandle { return $this->handle; }
}