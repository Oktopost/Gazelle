<?php
namespace Gazelle\Decorators;


use Gazelle\HTTPMethod;
use Gazelle\IResponseData;
use Gazelle\IRequestConfig;
use Gazelle\IRequestParams;
use Gazelle\AbstractConnectionDecorator;
use Gazelle\Exceptions\FatalGazelleException;


class RetryDecorator extends AbstractConnectionDecorator
{
	private $max = 3;
	
	
	private function executeSafe(IRequestParams $requestData): ?IResponseData
	{
		$originalRequest = clone $requestData;
		
		for ($i = 0; $i < $this->max; $i++)
		{
			try
			{
				return $this->invokeChild($originalRequest);
			}
			catch (ConnectionNotEstablishException $te)
			{
				continue;
			}
		}
		
		return null;
	}
	
	private function executeWithRetry(IRequestParams $requestData, IRequestConfig $config): IResponseData
	{
		$result = $this->executeSafe($requestData, $config);
		
		if (!$result)
		{
			$result = $this->invokeChild($requestData, $config);
		}
		
		return $result;
	}
	
	
	protected function shouldRetry(IRequestParams $requestData, IRequestConfig $config): bool
	{
		return true;
	}
	
	
	public function __construct(int $maxRetries = 3)
	{
		if ($maxRetries <= 0)
			throw new FatalGazelleException('Invalid value. Must be greater than zero');
		
		$this->max = $maxRetries;
	}
	
	
	public function request(IRequestParams $requestData): IResponseData
	{
		if ($requestData->getMethod() != HTTPMethod::GET)
		{
			return $this->invokeChild($requestData);
		}
		
		if ($this->shouldRetry($requestData, $config))
		{
			return $this->executeWithRetry($requestData, $config);
		}
		else
		{
			return $this->invokeChild($requestData, $config);
		}
	}
}