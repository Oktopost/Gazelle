<?php
namespace Gazelle;


class RequestConfig implements IRequestConfig
{
	private $connectionTimeout	= 10.0;
	private $requestTimeout		= 10.0;
	private $maxRedirects		= 3;
	
	
	public function getConnectionTimeout(): float
	{
		return $this->connectionTimeout;
	}
	
	public function getRequestTimeout(): float
	{
		return $this->requestTimeout;
	}
	
	public function getMaxRedirects(): float
	{
		return $this->maxRedirects;
	}
	
	
	public function setTimeout(float $connectionSec, ?float $requestSec = null): void
	{
		$this->connectionTimeout = $connectionSec;
		$this->requestTimeout = $requestSec;
	}
	
	public function setConnectionTimeout(float $sec): void
	{
		$this->connectionTimeout = $sec;
	}
	
	public function setRequestTimeout(float $sec): void
	{
		$this->requestTimeout = $sec;
	}
	
	public function setMaxRedirects(int $max): void
	{
		$this->maxRedirects = $max;
	}
}