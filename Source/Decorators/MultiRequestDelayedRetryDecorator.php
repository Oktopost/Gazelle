<?php
namespace Gazelle\Decorators;


use Gazelle\Multi\MultiResult;
use Gazelle\Multi\MultiRequest;
use Gazelle\Multi\IMultiExecutor;


class MultiRequestDelayedRetryDecorator extends MultiRequestRetryDecorator
{
	private array $toRetry = [];
	private int $retryIteration = 0;
	private int $requestsToProcess = 0;
	
	
	private function retry(): void
	{
		if ($this->retryIteration > $this->getMaxRetries())
			return;
		
		$this->retryIteration++;
		
		sleep($this->retryIteration);
		
		$toRetry = $this->toRetry;
		$this->toRetry = [];
		
		foreach ($toRetry as $multiResult) 
		{
			$this->requestsToProcess++;
			$this->sendUsing($multiResult);
		}
	}
	
	
	protected function onRequestException(MultiResult $result): ?MultiResult
	{
		$this->requestsToProcess--;
		
		if ($this->retryIteration > $this->getMaxRetries())
			return $result;
		
		$this->toRetry[] = $result;
		
		return null;
	}
	
	
	public function send(MultiRequest $request, IMultiExecutor $executor): MultiResult
	{
		$this->requestsToProcess++;
		return parent::send($request, $executor);
	}
	
	public function next(float $timeout = 0.1): ?MultiResult
	{
		$result = parent::next($timeout);
		
		if ($this->requestsToProcess == 0 && $this->toRetry)
		{
			$this->retry();
		}
		
		return $result;
	}
}