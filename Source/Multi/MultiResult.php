<?php
namespace Gazelle\Multi;


use Gazelle\IResponse;
use Gazelle\IRequestParams;
use Gazelle\Exceptions\GazelleException;


class MultiResult implements IMultiResult
{
	private MultiRequest	$request;
	private IMultiExecutor	$executor;
	private ?IResponse		$response	= null;
	private ?\Throwable		$error		= null;
	
	private array	$onComplete		= [];
	private bool	$completeCalled	= false;
	
	
	public function __construct(MultiRequest $request, IMultiExecutor $executor)
	{
		$this->request	= $request;
		$this->executor	= $executor;
	}
	
	
	public function request(): IRequestParams
	{
		return $this->request;
	}
	
	public function response(): ?IResponse
	{
		return $this->response;
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
		if (!$this->executor->abort($this))
			return false;
		
		$this->reset();
		
		return true;
	}
	
	
	public function setResult(?IResponse $response, ?\Throwable $error): void
	{
		if ($this->isExecuted())
			throw new GazelleException('setResult called more than once for multi-request!');
		
		$this->response = $response;
		$this->error	= $error;
	}
	
	public function reset(): void
	{
		$this->response = null;
		$this->error	= null;
	}
	
	public function complete(): void
	{
		if ($this->completeCalled)
			throw new GazelleException('Complete called more than once for multi-request!');
		
		$this->completeCalled = true;
		
		$all = $this->onComplete;
		$this->onComplete = [];
		
		foreach ($all as $callback)
		{
			$callback($this);
		}
	}
}