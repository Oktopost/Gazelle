<?php
namespace Gazelle\Exceptions;


use Gazelle\IRequestParams;


class RequestException extends GazelleException 
{
	/** @var IRequestParams */
	private $request;
	
	
	private function getRuntimeAsString(float $runtime): string
	{
		return round($runtime, 4);
	}
	
	
	public function __construct(IRequestParams $requestData, float $runtime, $code = 0)
	{
		parent::__construct("Connection timeout out after {$this->getRuntimeAsString($runtime)} seconds", $code);
		
		$this->request = clone $requestData;
	}
	
	public function request(): IRequestParams
	{
		return $this->request;
	}
}