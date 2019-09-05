<?php
namespace Gazelle;


use Gazelle\Utils\OptionsConfig;


class RequestConfig implements IRequestConfig
{
	private $connectionTimeout	= 10.0;
	private $executionTimeout	= 10.0;
	private $maxRedirects		= 3;
	private $curlOptions		= [CURLOPT_RETURNTRANSFER => 1];
	
	
	public function getConnectionTimeout(): float
	{
		return $this->connectionTimeout;
	}
	
	public function getExecutionTimeout(): float
	{
		return $this->executionTimeout;
	}
	
	public function getMaxRedirects(): int
	{
		return $this->maxRedirects;
	}
	
	public function getCurlOptions(): array
	{
		return $this->curlOptions;
	}
	
	public function hasCurlOptions(): bool
	{
		return (bool)$this->curlOptions;
	}
	
	
	public function setConnectionTimeout(float $sec): IRequestConfig
	{
		$this->connectionTimeout = $sec;
		return $this;
	}
	
	public function setExecutionTimeout(float $sec, ?float $connectionSec = null): IRequestConfig
	{
		$this->executionTimeout = $sec;
		
		if ($connectionSec)
		{
			$this->connectionTimeout = $connectionSec;
		}
		
		return $this;
	}
	
	public function setMaxRedirects(int $max): IRequestConfig
	{
		$this->maxRedirects = $max;
		return $this;
	}
	
	public function setCurlOption(int $option, $value): IRequestConfig
	{
		$this->curlOptions[$option] = $value;
		return $this;
	}
	
	public function setCurlOptions(array $options): IRequestConfig
	{
		$this->curlOptions = array_merge($this->curlOptions, $options);
		return $this;
	}
	
	
	public function toCurlOptions(): array
	{
		return
			$this->curlOptions +
			OptionsConfig::setRedirects($this) + 
			OptionsConfig::setTimeouts($this) + 
			[ 
				CURLOPT_HEADER	=> 1
			];
	}
}