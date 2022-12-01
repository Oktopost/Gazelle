<?php
namespace Gazelle\Decorators;


use Gazelle\IResponse;
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
	
	
	public function request(IRequestParams $requestData): IResponse
	{
		$callback = $this->callback;
		$result = $callback($requestData);
		
		if (!($result instanceof IResponse))
		{
			throw new FatalGazelleException('Return type of a callback decorator must be an instance of IResponse');
		}
		
		return $result;
	}
}