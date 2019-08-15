<?php
namespace Gazelle;


use Gazelle\Decorators\CallbackDecorator;
use Structura\Arrays;
use Gazelle\Builders\CallbackBuilder;
use Gazelle\Builders\InstanceBuilder;
use Gazelle\Exceptions\FatalGazelleException;


class ConnectionBuilder implements IConnectionBuilder
{
	/** @var IConnectionDecorator[]|string[]|callable */
	private $decorators = [];
	
	/** @var IConnection */
	private $connection = null;
	
	/** @var IConnectionBuilder */
	private $connectionProvider = null;
	
	
	private function getConnection(): IConnection
	{
		if ($this->connection)
		{
			return $this->connection;
		}
		else if ($this->connectionProvider)
		{
			return $this->connectionProvider->get();
		}
		else
		{
			throw new FatalGazelleException('No default connection was setup');
		}
	}
	
	private function getDecorator($decorator): IConnectionDecorator
	{
		if ($decorator instanceof IConnectionDecorator)
		{
			return $decorator;
		}
		else if (is_callable($decorator))
		{
			return new CallbackDecorator($decorator);
		}
		else if (is_string($decorator))
		{
			return new $decorator;
		}
		else
		{
			throw new FatalGazelleException('Got unexpected type for a decorator');
		}
	}
	
	
	public function setMainObject($connection)
	{
		if (is_callable($connection))
		{
			$this->setMainObject(new CallbackBuilder($connection));
		}
		else if (is_string($connection))
		{
			$this->setMainObject(new InstanceBuilder($connection));
		}
		else if ($connection instanceof IConnection)
		{
			$this->connection = $connection;
			$this->connectionProvider = null;
		}
		else if ($connection instanceof IConnectionBuilder)
		{
			$this->connectionProvider = $connection;
			$this->connection = null;
		}
		else
		{
			throw new FatalGazelleException(
				'Invalid value provided for connection. Expecting: ' . 
				'string, callback, IConnection or IConnectionBuilder instance');
		}
	}
	
	public function addDecorators($decorators, bool $last): void
	{
		if ($last)
		{
			$this->decorators = Arrays::merge($this->decorators, $decorators);
		}
		else
		{
			$this->decorators = Arrays::merge($decorators, $this->decorators);
		}
	}
	
	
	public function get(): IConnection
	{
		$connection = $this->getConnection();
		
		foreach ($this->decorators as $decorator)
		{
			$decorator = $this->getDecorator($decorator);
			$decorator->setChild($connection);
			$connection = $decorator;
		}
		
		return $connection;
	}
}