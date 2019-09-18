<?php
namespace Gazelle\Exceptions;


use Gazelle\IRequestParams;


class RequestException extends GazelleException 
{
	/** @var IRequestParams */
	private $request;
	
	
	public function __construct(IRequestParams $requestData, string $message, int $code = 0, ?\Throwable $t = null)
	{
		parent::__construct($message, $code, $t);
		$this->request = clone $requestData;
	}
	
	
	public function request(): ?IRequestParams
	{
		return $this->request;
	}
}