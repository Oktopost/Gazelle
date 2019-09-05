<?php
namespace Gazelle;


use Gazelle\Connections\CurlConnection;


class Gazelle
{
	/** @var IRequestConfig */
	private $config;
	
	/** @var Request */
	private $template;
	
	/** @var ConnectionBuilder */
	private $builder;
	
	
	public function __construct()
	{
		$this->config = new RequestConfig();
		$this->builder = new ConnectionBuilder();
		$this->template = new Request($this->config, $this->builder);
		
		$this->builder->setMainObject(CurlConnection::class);
	}
	
	
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
	
	
	public function config(): IRequestConfig
	{
		return $this->config;
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