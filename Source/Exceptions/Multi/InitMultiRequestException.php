<?php
namespace Gazelle\Exceptions\Multi;


use Gazelle\Multi\MultiRequest;
use Gazelle\Exceptions\MultiCurlException;


class InitMultiRequestException extends MultiCurlException
{
	private int				$curlCode;
	private MultiRequest	$request;
	
	
	public function __construct(int $curlCode, MultiRequest $request)
	{
		parent::__construct();
		
		$this->curlCode	= $curlCode;
		$this->request = $request;
	}
	
	
	public function getCurlCode(): int { return $this->curlCode; }
	public function getRequest(): MultiRequest { return $this->request; }
}