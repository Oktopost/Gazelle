<?php
namespace Gazelle\Decorators;


use Gazelle\IResponseData;
use Gazelle\IRequestParams;
use Gazelle\AbstractConnectionDecorator;


abstract class AbstractRetryDecorator extends AbstractConnectionDecorator
{
	private $maxRetries = 1;
	
	
	private function requestWithRetries(IRequestParams $requestData): IResponseData
	{
		$requestNumber = 1;
		
		while ($requestNumber++ < $this->maxRetries)
		{
			$result = $this->executeOnce($requestData);
			
			if ($result)
			{
				return $result;
			}
		}
		
		return $this->invokeChild($requestData);
	}
	
	
	protected abstract function executeOnce(IRequestParams $requestData): ?IResponseData;
	protected abstract function shouldRetry(IRequestParams $requestData): bool;
	
	
	protected function executeFinalTime(IRequestParams $requestData): IResponseData
	{
		return $this->invokeChild($requestData);
	}
	
	protected function getMaxRetries(): int
	{
		return $this->maxRetries;
	}
	
	
	public function setMaxRetries(int $max): AbstractRetryDecorator
	{
		$this->maxRetries = $max;
		return $this;
	}
	
	public function request(IRequestParams $requestData): IResponseData
	{
		if (!$this->shouldRetry($requestData))
		{
			return $this->invokeChild($requestData);
		}
		else
		{
			return $this->requestWithRetries($requestData);
		}
	}
}