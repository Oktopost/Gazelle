<?php
namespace Gazelle\Decorators;


use Gazelle\IResponseData;
use Gazelle\IRequestParams;
use Gazelle\AbstractConnectionDecorator;
use Gazelle\Exceptions\FatalGazelleException;


class CallbackDecorator extends AbstractConnectionDecorator
{
	private $callback;
	
	
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}
	
	
	public function request(IRequestParams $requestData): IResponseData
	{
		$callback = $this->callback;
		$result = $callback($requestData);
		
		if (!($result instanceof IResponseData))
		{
			throw new FatalGazelleException('Return type of a callback decorator must be an instance of IResponseData');
		}
		
		return $result;
	}
}