<?php
namespace Gazelle\Exceptions\Request;


use Gazelle\IResponseData;
use Gazelle\Exceptions\RequestException;


class TimeoutException extends RequestException
{
	private function getRuntimeAsString(float $runtime): string
	{
		return round($runtime, 4);
	}
	
	
	public function __construct(IResponseData $responseData)
	{
		$runtime = $responseData->requestMetaData()->getRuntime();
		$runtime = $this->getRuntimeAsString($runtime);
		
		parent::__construct(
			$responseData->getRequestParams(),
			"Connection timeout out after $runtime seconds",
			CURLE_OPERATION_TIMEOUTED);
	}
}