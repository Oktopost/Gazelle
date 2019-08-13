<?php
namespace Gazelle\Decorators;


use Gazelle\IRequestData;
use Gazelle\IResponseData;
use Gazelle\IRequestConfig;
use Gazelle\AbstractConnectionDecorator;
use Gazelle\Exceptions\ConnectionNotEstablishException;
use Gazelle\Exceptions\FatalGazelleException;


class RetryDecorator extends AbstractConnectionDecorator
{
	private $max = 3;
	
	
	private function executeSafe(IRequestData $requestData, IRequestConfig $config): ?IResponseData
	{
		$originalRequest	= clone $requestData;
		$originalConfig		= clone $config;
		
		for ($i = 0; $i < $this->max; $i++)
		{
			try
			{
				return $this->invokeChild($originalRequest, $originalConfig);
			}
			catch (ConnectionNotEstablishException $te)
			{
				continue;
			}
		}
		
		return null;
	}
	
	private function executeWithRetry(IRequestData $requestData, IRequestConfig $config): IResponseData
	{
		$result = $this->executeSafe($requestData, $config);
		
		if (!$result)
		{
			$result = $this->invokeChild($requestData, $config);
		}
		
		return $result;
	}
	
	
	protected function shouldRetry(IRequestData $requestData, IRequestConfig $config): bool
	{
		return true;
	}
	
	
	public function __construct(int $maxRetries = 3)
	{
		if ($maxRetries <= 0)
			throw new FatalGazelleException('Invalid value. Must be greater than zero');
		
		$this->max = $maxRetries;
	}
	
	
	public function request(IRequestData $requestData, IRequestConfig $config): IResponseData
	{
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