<?php
namespace Gazelle\Exceptions;


class CertificateInfoException extends GazelleException
{
	/** @var array|null */
	private $response; 
	
	private $errorCode;
	private $errorString;
	
	
	public function __construct(string $message, ?array $data = null, int $errorCode = 0, string $errorString = '')
	{
		parent::__construct($message, $errorCode);
		
		$this->response = $data;
		$this->errorCode = $errorCode;
		$this->errorString = $errorString;
	}
	
	
	public function getResponse(): ?array 
	{
		return $this->response;
	}
	
	public function getErrorCode(): int 
	{
		return $this->errorCode;
	}
	
	public function getErrorString(): int 
	{
		return $this->errorString;
	}
}