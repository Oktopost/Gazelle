<?php
namespace Gazelle\Exceptions;


use Gazelle\IRequestSettings;
use Gazelle\IRequestConfig;


class RequestException extends GazelleException 
{
	/** @var IRequestConfig */
	private $config;
	
	/** @var IRequestSettings */
	private $request;
	
	
	public function __construct(IRequestSettings $requestData, IRequestConfig $config, $message = '', $code = 0)
	{
		parent::__construct($message, $code);
		
		$this->config = $config;
		$this->request = $requestData;
	}
	
	public function request(): IRequestSettings
	{
		return $this->request;
	}
	
	public function requestConfig(): IRequestConfig
	{
		return $this->config;
	}
}