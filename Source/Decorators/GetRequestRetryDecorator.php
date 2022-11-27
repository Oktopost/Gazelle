<?php
namespace Gazelle\Decorators;


use Gazelle\Exceptions\Request\TimeoutException;
use Gazelle\Exceptions\ResponseException;
use Gazelle\HTTPMethod;
use Gazelle\IRequestParams;
use Gazelle\IResponse;


class GetRequestRetryDecorator extends RequestRetryDecorator
{
	protected function shouldRetry(IRequestParams $requestData): bool
	{
		return 
			$requestData->getMethod() == HTTPMethod::GET || 
			$requestData->getMethod() == HTTPMethod::HEAD;
	}
}