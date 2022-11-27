<?php
namespace Gazelle\Decorators;


use Gazelle\Exceptions\Request\TimeoutException;
use Gazelle\Exceptions\ResponseException;
use Gazelle\IRequestParams;
use Gazelle\IResponse;


class RequestRetryDecorator extends AbstractRetryDecorator
{
	protected function executeOnce(IRequestParams $requestData): ?IResponse
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
		return true;
	}
}