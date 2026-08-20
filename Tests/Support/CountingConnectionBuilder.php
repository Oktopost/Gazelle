<?php
namespace Gazelle\Tests\Support;


use Gazelle\IConnection;
use Gazelle\IConnectionBuilder;


class CountingConnectionBuilder implements IConnectionBuilder
{
	public int $calls = 0;


	public function __construct(private IConnection $connection)
	{
	}

	public function get(): IConnection
	{
		$this->calls++;
		return clone $this->connection;
	}
}
