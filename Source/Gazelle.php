<?php
namespace Gazelle;


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
	}
	
	
	public function setConnection($connection): Gazelle
	{
		$this->builder->setMainObject($connection);
		return $this;
	}
	
	public function addDecorator($decorator): Gazelle
	{
		$this->builder->addDecorators($decorator);
		return $this;
	}
	
	
	public function config(): IRequestConfig
	{
		return $this->config;
	}
	
	public function template(): IRequestData
	{
		return $this->template; 
	}
	
	public function request()
	{
		return clone $this->template;
	}
}