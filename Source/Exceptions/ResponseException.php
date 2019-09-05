<?php
namespace Gazelle\Exceptions;


use Gazelle\IRequestSettings;
use Gazelle\IResponseData;
use Gazelle\IRequestConfig;


abstract class ResponseException extends GazelleException
{
	private $response;
	
	
	public function __construct(IResponseData $data, $message = "")
	{
		parent::__construct($message);
		$this->response = $data;
	}
	
	
	public function response(): IResponseData
	{
		return $this->response;
	}
	
	public function request(): IRequestSettings
	{
		return $this->response->requestData();
	}
	
	public function requestConfig(): IRequestConfig
	{
		return $this->response->requestConfig();
	}
	
	public function code(): int
	{
		return $this->response->getCode();
	}
	
	public function isServerError(): bool
	{
		return (int)($this->response->getCode() / 100) * 100 == 500;
	}
	
	public function isClientError(): bool
	{
		return (int)($this->response->getCode() / 100) * 100 == 400;
	}
}