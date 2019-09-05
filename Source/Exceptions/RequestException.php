<?php
namespace Gazelle\Exceptions;


use Gazelle\IRequestParams;
use Gazelle\IRequestConfig;


class RequestException extends GazelleException 
{
	/** @var IRequestConfig */
	private $config;
	
	/** @var IRequestParams */
	private $request;
	
	
	public function __construct(IRequestParams $requestData, IRequestConfig $config, $message = '', $code = 0)
	{
		parent::__construct($message, $code);
		
		$this->config = $config;
		$this->request = $requestData;
	}
	
	public function request(): IRequestParams
	{
		return $this->request;
	}
	
	public function requestConfig(): IRequestConfig
	{
		return $this->config;
	}
}