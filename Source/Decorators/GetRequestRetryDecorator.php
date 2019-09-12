<?php
namespace Gazelle\Decorators;


use Gazelle\Exceptions\Request\TimeoutException;
use Gazelle\Exceptions\ResponseException;
use Gazelle\HTTPMethod;
use Gazelle\IRequestParams;
use Gazelle\IResponseData;

class GetRequestRetryDecorator extends AbstractRetryDecorator
{
	protected function executeOnce(IRequestParams $requestData): ?IResponseData
	{
		try
		{
			$result = $this->invokeChild($requestData);
		}
		catch (TimeoutException $te)
		{
			return null;
		}
		catch (ResponseException $re)
		{
			if ($re->isServerError())
			{
				return null;
			}
			else
			{
				throw $re;
			}
		}
		
		if ($result->isFailed() && $result->isServerError())
		{
			return null;
		}
		
		return $result;
	}
	
	protected function shouldRetry(IRequestParams $requestData): bool
	{
		return 
			$requestData->getMethod() == HTTPMethod::GET || 
			$requestData->getMethod() == HTTPMethod::HEAD;
	}
	
	
}