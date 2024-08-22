<?php
namespace Gazelle\Multi;


use Gazelle\IRequestParams;
use Gazelle\IResponse;
use Gazelle\Exceptions\GazelleException;


class MultiResult implements IMultiResult
{
	private IRequestParams	$request;
	private IMultiExecutor	$executor;
	
	private ?IResponse		$response	= null;
	private ?\Throwable		$error		= null;
	
	private array $onComplete	= [];
	
	
	public function __construct(IRequestParams $request, IMultiExecutor $executor)
	{
		$this->request	= $request;
		$this->executor	= $executor;
	}
	
	
	public function request(): IRequestParams
	{
		return $this->request;
	}
	
	public function response(): IResponse
	{
		return $this->response;
	}
	
	public function metadata(): mixed
	{
		return $this->request->getMetadata();
	}
	
	public function error(): ?\Throwable
	{
		return $this->error;
	}
	
	public function hasError(): bool
	{
		return (bool)($this->error);
	}
	
	public function isExecuted(): bool
	{
		return $this->response || $this->error;
	}
	
	public function hasMetadata(): bool
	{
		return !is_null($this->request->hasMetadata());
	}
	
	public function onComplete(callable $callback): void
	{
		if ($this->isExecuted())
		{
			$callback($this);
		}
		else
		{
			$this->onComplete[] = $callback;
		}
	}
	
	public function abort(): bool
	{
		if ($this->isExecuted())
			return false;
		
		return $this->executor->abort($this);
	}
	
	
	public function complete(?IResponse $response, ?\Throwable $error): void
	{
		if ($this->isExecuted())
			throw new GazelleException('Complete called more than once for multi-request!');
			
		$this->response = $response;
		$this->error	= $error;
		
		foreach ($this->onComplete as $callback)
		{
			$callback($this);
		}
		
		$this->onComplete = [];
	}
}