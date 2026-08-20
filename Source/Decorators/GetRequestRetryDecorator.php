<?php
namespace Gazelle\Decorators;


use Gazelle\HTTPMethod;
use Gazelle\IRequestParams;


class GetRequestRetryDecorator extends RequestRetryDecorator
{
	protected function shouldRetry(IRequestParams $requestData): bool
	{
		return 
			$requestData->getMethod() == HTTPMethod::GET || 
			$requestData->getMethod() == HTTPMethod::HEAD;
	}
}