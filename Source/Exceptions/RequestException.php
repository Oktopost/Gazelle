<?php
namespace Gazelle\Exceptions;


use Gazelle\IRequestData;
use Gazelle\IRequestConfig;


class RequestException extends GazelleException 
{
	/** @var IRequestConfig */
	private $config;
	
	/** @var IRequestData */
	private $request;
	
	
	public function __construct(IRequestData $requestData, IRequestConfig $config, $message = '', $code = 0)
	{
		parent::__construct($message, $code);
		
		$this->config = $config;
		$this->request = $requestData;
	}
	
	public function request(): IRequestData
	{
		return $this->request;
	}
	
	public function requestConfig(): IRequestConfig
	{
		return $this->config;
	}
}