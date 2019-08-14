<?php
namespace Gazelle\Decorators;


use Gazelle\Exceptions\FatalGazelleException;
use Gazelle\IRequestData;
use Gazelle\IResponseData;
use Gazelle\IRequestConfig;
use Gazelle\AbstractConnectionDecorator;


class CallbackDecorator extends AbstractConnectionDecorator
{
	private $callback;
	
	
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}
	
	
	public function request(IRequestData $requestData, IRequestConfig $config): IResponseData
	{
		$callback = $this->callback;
		$result = $callback($requestData, $config);
		
		if (!($result instanceof IResponseData))
		{
			throw new FatalGazelleException('Return type of a callback decorator must be an instance of IResponseData');
		}
		
		return $result;
	}
}