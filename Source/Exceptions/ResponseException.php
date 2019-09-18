<?php
namespace Gazelle\Exceptions;


use Gazelle\IResponseData;
use Gazelle\IRequestParams;


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
	
	public function request(): ?IRequestParams
	{
		return $this->response->getRequestParams();
	}
	
	public function code(): int
	{
		return $this->response->getCode();
	}
	
	public function isServerError(): bool
	{
		return $this->response->isServerError();
	}
	
	public function isClientError(): bool
	{
		return $this->response->isClientError();
	}
}