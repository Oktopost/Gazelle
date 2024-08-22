<?php
namespace Gazelle\Multi;


use Gazelle\RequestParams;
use Gazelle\ConnectionBuilder;


class MultiController implements IMultiExecutor
{
	private RequestParams		$template;
	private ConnectionBuilder	$builder;
	
	
	/** @var MultiResult[] */
	private array $pending = [];
	
	/** @var MultiResult[] */
	private array $next = [];
	
	
	public function __construct(RequestParams $template, ConnectionBuilder $builder)
	{
		$this->template	= RequestParams::makeCopy($template);
		$this->builder	= $builder;
	}
	
	
	public function request($path = null, array $headers = [], mixed $metadata = null): MultiRequest
	{
		$request = new MultiRequest($this);
		
		$request->copy($this->template);
		
		if ($path)
			$request->addPath($path);
		
		if ($headers)
			$request->setHeaders($headers);
		
		if ($metadata)
			$request->setMetadata($metadata);
		
		return $request;
	}
	
	public function execute(MultiRequest $request): IMultiResult
	{
		return new MultiResult($request, $this);
	}
	
	
	public function poll(): bool
	{
		
	}
	
	public function waitForNext(float $timeout = 0.0): bool
	{
		
	}
	
	public function waitForAll(): void
	{
		
	}
	
	public function pendingCount(): int
	{
		
	}
	
	public function completeCount(): int
	{
		
	}
	
	public function requestsCount(): int
	{
		return $this->pendingCount() + $this->completeCount();
	}
	
	public function hasNext(): bool
	{
		return (bool)($this->next);
	}
	
	public function hasPending(): bool
	{
		return (bool)($this->pending);
	}
	
	public function waitNext(): ?MultiResult
	{
		// TODO: 
	}
	
	public function next(): ?MultiResult
	{
		return array_shift($this->next);
	}
	
	public function all(): array
	{
		
	}
}