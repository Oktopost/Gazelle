<?php
namespace Gazelle\Builders;


use Gazelle\Exceptions\FatalGazelleException;
use Gazelle\IConnection;
use Gazelle\IConnectionBuilder;


class InstanceBuilder implements IConnectionBuilder
{
	private $className;
	
	
	public function __construct(string $className)
	{
		$this->className = $className;
	}
	
	
	public function get(): IConnection
	{
		$className = $this->className;
		$connection = new $className();
		
		if (!($connection instanceof IConnection))
		{
			throw new FatalGazelleException(
				"The class $className does not implement IConnection");
		}
		
		return $connection;
	}
}