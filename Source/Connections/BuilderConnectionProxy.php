<?php
namespace Gazelle\Connections;


use Gazelle\IConnection;
use Gazelle\IResponse;
use Gazelle\IRequestParams;
use Gazelle\IConnectionBuilder;


class BuilderConnectionProxy implements IConnection
{
	/** @var IConnectionBuilder */
	private $builder;
	
	
	public function __construct(IConnectionBuilder $builder)
	{
		$this->builder = $builder;
	}
	
	public function __clone()
	{
		// Do not clone the builder to insure same builder instance is used.
	}
	
	
	public function request(IRequestParams $requestData): IResponse
	{
		$connection = $this->builder->get();
		return $connection->request($requestData);
	}
}