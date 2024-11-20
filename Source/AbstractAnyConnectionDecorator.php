<?php
namespace Gazelle;


use Gazelle\Exceptions\GazelleException;
use Gazelle\Multi\IMultiConnection;
use Gazelle\Multi\IMultiConnectionDecorator;


abstract class AbstractAnyConnectionDecorator implements IConnectionDecorator, IMultiConnectionDecorator
{
	/** @var IConnection|IMultiConnection|null */
	private IConnection|IMultiConnection|null $child = null;
	
	
	protected function child(): IConnection|IMultiConnection
	{
		if (!$this->child)
		{
			throw new GazelleException('Child must be set!');
		}
		
		return $this->child;
	}
	
	
	public function isRunning(): bool
	{
		return $this->child->isRunning();
	}
	
	public function setChild(IConnection $connection): void
	{
		$this->child = $connection;
	}
	
	
	public function setNextConnection(IMultiConnection $connection): void
	{
		$this->child = $connection;
	}
}