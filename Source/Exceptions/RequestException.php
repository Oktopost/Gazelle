<?php
namespace Gazelle\Exceptions;


use Gazelle\IRequestParams;
use Gazelle\IRequestConfig;


class RequestException extends GazelleException 
{
	/** @var IRequestParams */
	private $request;
	
	
	public function __construct(IRequestParams $requestData, $message = '', $code = 0)
	{
		parent::__construct($message, $code);
		
		$this->request = clone $requestData;
	}
	
	public function request(): IRequestParams
	{
		return $this->request;
	}
	
	public function requestConfig(): IRequestConfig
	{
		return $this->request->getConfig();
	}
}