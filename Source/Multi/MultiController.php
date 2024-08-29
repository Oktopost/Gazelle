<?php
namespace Gazelle\Multi;


use Gazelle\Exceptions\GazelleException;
use Gazelle\GazelleMock;
use Gazelle\RequestParams;
use Gazelle\Connections\MultiCurlConnection;


class MultiController implements IMultiExecutor
{
	private RequestParams		$template;
	private IMultiConnection	$connection;
	
	
	/** @var MultiResult[] */
	private array $pending = [];
	
	/** @var MultiResult[] */
	private array $next = [];
	
	
	private function consumeNext(float $timeout = 0.0): void
	{
		$result = $this->connection->next($timeout);
		
		while ($result != null)
		{
			$this->removePending($result);
			
			$this->next[] = $result;
			$result->complete();
			
			$result = $this->connection->next(0.0);
		}
	}
	
	private function removePending(MultiResult $result): void
	{
		$index = array_search($result, $this->pending, true);
		
		if ($index === false)
		{
			throw new GazelleException('Got result that is not part of the pending set!');
		}
		
		array_splice($this->pending, $index, 1);
	}
	
	
	public function __construct(RequestParams $template) //, ConnectionBuilder $builder)
	{
		$this->template		= RequestParams::makeCopy($template);
		$this->connection	= GazelleMock::$multiConnection ?? new MultiCurlConnection();
	}
	
	
	public function getConnection(): IMultiConnection
	{
		return $this->connection;
	}
	
	public function setConnection(IMultiConnection $connection): void
	{
		$this->connection = $connection;
	}
	
	
	public function request($path = null, array $headers = []): MultiRequest
	{ 
		$request = new MultiRequest($this);
		
		$request->copy($this->template);
		
		if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))
			$request->setURL($path);
		else if ($path)
			$request->addPath($path);
		
		if ($headers)
			$request->setHeaders($headers);
		
		return $request;
	}
	
	public function execute(MultiRequest $request): MultiResult
	{
		$result = $this->connection->send($request, $this);
		
		$this->pending[] = $result;
		
		return $result;
	}
	
	public function abort(MultiResult $result): bool
	{
		if ($result->isExecuted())
			return false;
		
		if (!in_array($result, $this->pending, true))
			return false;
		
		if (!$this->connection->abort($result))
			return false;
		
		$this->removePending($result);
		
		return true;
	}
	
	public function waitForNext(float $timeout = 0.0): bool
	{
		if ($this->hasNext())
			return true;
		
		$this->consumeNext($timeout);
		
		return $this->hasNext();
	}
	
	public function waitForAll(float $timeout = 0.0): bool
	{
		if (!$this->hasPending())
			return true;
		
		$endTime = microtime(true) + $timeout;
		
		$this->consumeNext($timeout);
		
		$now = microtime(true);
		
		while ($this->hasPending() && $now <= $endTime)
		{
			$this->consumeNext($timeout);
			$now = microtime(true);
		}
		
		return !$this->hasPending();
	}
	
	public function pendingCount(): int
	{
		return count($this->pending);
	}
	
	public function readyCount(): int
	{
		return count($this->next);
	}
	
	public function requestsCount(): int
	{
		return $this->pendingCount() + $this->readyCount();
	}
	
	public function hasPending(): bool
	{
		return (bool)($this->pending);
	}
	
	public function hasNext(): bool
	{
		return (bool)($this->next);
	}
	
	public function hasAny(): bool
	{
		return (bool)($this->next) || (bool)($this->pending);
	}
	
	public function isEmpty(): bool
	{
		return 
			!$this->hasPending() && 
			!$this->hasNext();
	}
	
	public function executeAll(): void
	{
		$this->allNext(-1);
	}
	
	
	public function next(float $timeout = 1.0): ?MultiResult
	{
		if (!$this->hasNext())
			$this->waitForNext($timeout);
		
		return array_shift($this->next);
	}
	
	/**
	 * @param float $timeout
	 * @return MultiResult[]
	 */
	public function allNext(float $timeout = 30.0): array
	{
		$this->waitForAll($timeout);
		
		$all = $this->next;
		$this->next = [];
		
		return $all;
	}
	
	
	public function reset(): void
	{
		$this->connection->close();
		
		$this->pending	= [];
		$this->next		= [];
	}
}