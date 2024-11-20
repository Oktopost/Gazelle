<?php
namespace Gazelle;


use Gazelle\Multi\MultiResult;
use Gazelle\Multi\IMultiConnection;
use Gazelle\Multi\IMultiConnectionDecorator;
use Gazelle\Exceptions\GazelleException;


abstract class AbstractMultiDecorator implements IMultiConnectionDecorator
{
	private ?IMultiConnection $child = null;
	
	
	protected function child(): IMultiConnection
	{
		if (!$this->child)
			throw new GazelleException('Child must be set!');
		
		return $this->child;
	}
	
	public function setNextConnection(IMultiConnection $connection): void
	{
		$this->child = $connection;
	}
	
	public function isRunning(): bool
	{
		return $this->child()->isRunning();
	}
	
	public function sendUsing(MultiResult $result): void
	{
		$this->child()->sendUsing($result);
	}
	
	public function abort(MultiResult $result): bool
	{
		return $this->child()->abort($result);
	}
}