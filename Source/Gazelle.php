<?php
namespace Gazelle;


use Gazelle\Connections\CurlConnection;
use Gazelle\Connections\BuilderConnectionProxy;


class Gazelle
{
	/** @var Request */
	private $template;
	
	/** @var ConnectionBuilder */
	private $builder;
	
	
	public function __construct()
	{
		$this->builder = new ConnectionBuilder();
		$this->template = new Request(new BuilderConnectionProxy($this->builder));
		
		$this->builder->setMainObject(CurlConnection::class);
	}
	
	/**
	 * @param string|IConnection|IConnectionBuilder $connection
	 * @return Gazelle
	 */
	public function setConnection($connection): Gazelle
	{
		$this->builder->setMainObject($connection);
		return $this;
	}
	
	public function addDecorator($decorator, bool $last = true): Gazelle
	{
		$this->builder->addDecorators($decorator, $last);
		return $this;
	}
	
	public function reuseConnection(bool $reuse): Gazelle
	{
		$this->builder->reuseConnection($reuse);
		return $this;
	}
	
	
	public function template(): IRequestParams
	{
		return $this->template; 
	}
	
	public function request($url = null, array $headers = []): Request
	{
		$request = clone $this->template;
		
		if ($url)
			$request->setURL($url);
		
		if ($headers)
			$request->setHeaders($headers);
		
		return $request;
	}
}