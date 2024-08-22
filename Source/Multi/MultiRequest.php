<?php
namespace Gazelle\Multi;


use Gazelle\HTTPMethod;
use Gazelle\RequestParams;
use Gazelle\Exceptions\GazelleException;


class MultiRequest extends RequestParams
{
	private mixed					$metadata	= null;
	private ?IMultiExecutor	$handler;
	
	
	private function execute(): MultiResult
	{
		if (!$this->handler)
			throw new GazelleException('MutliRequest can not be executed more than once');
		
		$handler = $this->handler;
		$this->handler = null;
		
		return $handler->execute($this);
	}
	
	
	public function __construct(IMultiExecutor $handler)
	{
		parent::__construct();
		
		$this->handler = $handler;
	}
	
	public function __clone()
	{
		if (!$this->handler)
			throw new GazelleException('MutliRequest can be cloned only before it\'s executed');
		
		parent::__clone();
	}
	
	
	public function setMetadata(mixed $metadata): MultiRequest
	{
		$this->metadata = $metadata;
		return $this;
	}
	
	public function getMetadata(): mixed
	{
		return $this->metadata;
	}
	
	public function hasMetadata(): bool
	{
		return !is_null($this->metadata);
	}
	
	
	public function get(): MultiResult
	{
		$this->setMethod(HTTPMethod::GET);
		return $this->execute();
	}
	
	public function put(): MultiResult
	{
		$this->setMethod(HTTPMethod::PUT);
		return $this->execute();
	}
	
	public function post(): MultiResult
	{
		$this->setMethod(HTTPMethod::POST);
		return $this->execute();
	}
	
	public function head(): MultiResult
	{
		$this->setMethod(HTTPMethod::HEAD);
		return $this->execute();
	}
	public function delete(): MultiResult
	{
		$this->setMethod(HTTPMethod::DELETE);
		return $this->execute();
	}
	
	public function options(): MultiResult
	{
		$this->setMethod(HTTPMethod::OPTIONS);
		return $this->execute();
	}
	
	public function patch(): MultiResult
	{
		$this->setMethod(HTTPMethod::PATCH);
		return $this->execute();
	}
	
	public function send(): MultiResult
	{
		return $this->execute();
	}
}