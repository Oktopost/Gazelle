<?php
namespace Gazelle;


use Gazelle\Exceptions\Response\JSONExpectedException;


class Request extends RequestData implements IRequest
{
	/** @var IRequestConfig */
	private $config;
	
	/** @var IConnectionBuilder */
	private $builder;
	
	
	public function __construct(IRequestConfig $config, IConnectionBuilder $builder)
	{
		parent::__construct();
		$this->config = $config;
		$this->builder = $builder;
	}
	
	public function __clone()
	{
		$this->config = clone $this->config;
		$this->builder = clone $this->builder;
	}
	
	
	public function send(): IResponseData
	{
		$connection = $this->builder->get();
		return $connection->request($this, $this->config);
	}
	
	public function get(): IResponseData
	{
		$this->setMethod(HTTPMethod::GET);
		return $this->send();
	}
	
	public function put(): IResponseData
	{
		$this->setMethod(HTTPMethod::PUT);
		return $this->send();
	}
	
	public function post(): IResponseData
	{
		$this->setMethod(HTTPMethod::POST);
		return $this->send();
	}
	
	public function head(): IResponseData
	{
		$this->setMethod(HTTPMethod::HEAD);
		return $this->send();
	}
	
	public function delete(): IResponseData
	{
		$this->setMethod(HTTPMethod::DELETE);
		return $this->send();
	}
	
	public function options(): IResponseData
	{
		$this->setMethod(HTTPMethod::OPTIONS);
		return $this->send();
	}
	
	public function patch(): IResponseData
	{
		$this->setMethod(HTTPMethod::PATCH);
		return $this->send();
	}
	
	public function queryCode(): int
	{
		$this->setMethod(HTTPMethod::GET);
		return $this->send()->getCode();
	}
	
	public function queryOK(): bool
	{
		$this->setMethod(HTTPMethod::GET);
		return $this->send()->isSuccessful();
	}
	
	public function queryHeaders(): array
	{
		$this->setMethod(HTTPMethod::GET);
		return $this->send()->getHeaders();
	}
	
	public function queryBody(): string
	{
		$this->setMethod(HTTPMethod::GET);
		return $this->send()->getBody();
	}
	
	public function queryJSON(): array
	{
		$this->setMethod(HTTPMethod::GET);
		$result = $this->send()->getJSON();
		
		if (!is_array($result))
		{
			throw new JSONExpectedException($result);
		}
		
		return $result;
	}
	
	
	public function config(): IRequestConfig
	{
		return $this->config;
	}
	
	
	public function setCurlOption(int $option, $value): Request
	{
		$this->config->setCurlOption($option, $value);
		return $this;
	}
	
	public function setCurlOptions(array $options): Request
	{
		$this->config->setCurlOptions($options);
		return $this;
	}
	
	
	public function getAllCurlOptions(): array
	{
		return $this->config->toCurlOptions() + 
			$this->toCurlOptions();
	}
}