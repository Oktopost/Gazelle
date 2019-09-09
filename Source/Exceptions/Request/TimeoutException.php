<?php
namespace Gazelle\Exceptions\Request;


use Gazelle\IResponseData;
use Gazelle\Exceptions\RequestException;


class TimeoutException extends RequestException
{
	public function __construct(IResponseData $responseData)
	{
		parent::__construct(
			$responseData->getRequestParams(),
			$responseData->requestMetaData()->getRuntime(),
			CURLE_OPERATION_TIMEOUTED);
	}
}