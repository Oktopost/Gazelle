<?php
namespace Gazelle\Decorators;


use Gazelle\IResponse;
use Gazelle\IRequestParams;
use Gazelle\AbstractConnectionDecorator;


abstract class AbstractRetryDecorator extends AbstractConnectionDecorator
{
	private $maxRetries;
	
	
	private function requestWithRetries(IRequestParams $requestData): IResponse
	{
		$requestNumber = 1;
		
		while ($requestNumber++ < $this->maxRetries)
		{
			$request = clone $requestData;
			$result = $this->executeOnce($request);
			
			if ($result)
			{
				return $result;
			}
		}
		
		return $this->invokeChild($requestData);
	}
	
	
	protected abstract function executeOnce(IRequestParams $requestData): ?IResponse;
	protected abstract function shouldRetry(IRequestParams $requestData): bool;
	
	
	protected function executeFinalTime(IRequestParams $requestData): IResponse
	{
		return $this->invokeChild($requestData);
	}
	
	protected function getMaxRetries(): int
	{
		return $this->maxRetries;
	}
	
	
	public function __construct(int $maxRetries = 1)
	{
		$this->maxRetries = $maxRetries;
	}
	
	
	public function setMaxRetries(int $max): AbstractRetryDecorator
	{
		$this->maxRetries = $max;
		return $this;
	}
	
	public function request(IRequestParams $requestData): IResponse
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