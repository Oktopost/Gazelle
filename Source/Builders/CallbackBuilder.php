<?php
namespace Gazelle\Builders;


use Gazelle\IConnection;
use Gazelle\IConnectionBuilder;
use Gazelle\Exceptions\FatalGazelleException;


class CallbackBuilder implements IConnectionBuilder
{
	private $callback;
	
	
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}
	
	
	public function get(): IConnection
	{
		$callback = $this->callback;
		$connection = $callback();
		
		if (!($connection instanceof IConnection))
		{
			throw new FatalGazelleException(
				'The callback provided to create a new connection, does not return IConnection');
		}
		
		return $connection;
	}
}