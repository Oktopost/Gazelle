<?php
namespace Gazelle\Decorators;


use Gazelle\AbstractMultiDecorator;
use Gazelle\Multi\MultiResult;
use Gazelle\Multi\MultiRequest;
use Gazelle\Multi\IMultiExecutor;
use Gazelle\Exceptions\RequestException;


class MultiRequestRetryDecorator extends AbstractMultiDecorator
{
	private int $maxRetries;
	private array $retryCounters = [];
	
	/** @var MultiRequest[] */
	private array $allRequests = [];
	
	
	protected function getMaxRetries(): int
	{
		return $this->maxRetries;
	}
	
	protected function onRequestException(MultiResult $result): ?MultiResult
	{
		/** @var MultiRequest $request */
		$request = $result->request();
		
		$ref = array_search($request, $this->allRequests, true);
		
		if ($ref === false)
		{
			return $result;
		}
		
		if (!isset($this->retryCounters[$ref]))
		{
			$this->retryCounters[$ref] = 0;
		}
		
		if ($this->retryCounters[$ref] > $this->maxRetries)
		{
			return $result;
		}
		
		$this->retryCounters[$ref]++;
				
		$this->sendUsing($result);
		
		return null;
	}
	
	
	public function __construct(int $maxRetries = 5)
	{
		$this->maxRetries = $maxRetries;
	}
	
	
	public function send(MultiRequest $request, IMultiExecutor $executor): MultiResult
	{
		$hasRef = in_array($request, $this->allRequests, true);
		
		if (!$hasRef)
		{
			$this->allRequests[] = $request;
		}
		
		return $this->child()->send($request, $executor);
	}
	
	public function next(float $timeout = 0.1): ?MultiResult
	{
		$result = $this->child()->next($timeout);
		
		if ($result && $result->hasError() && $result->error() instanceof RequestException)
		{
			return $this->onRequestException($result);
		}
		
		return $result;
	}
}