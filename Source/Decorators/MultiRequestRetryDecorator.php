<?php
namespace Gazelle\Decorators;


use Gazelle\AbstractMultiDecorator;
use Gazelle\Multi\MultiResult;
use Gazelle\Multi\MultiRequest;
use Gazelle\Multi\IMultiExecutor;
use Gazelle\Exceptions\RequestException;
use Gazelle\Exceptions\ResponseException;


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

	private function forgetRequest(MultiRequest $request): void
	{
		$ref = array_search($request, $this->allRequests, true);

		if ($ref !== false)
		{
			unset($this->allRequests[$ref], $this->retryCounters[$ref]);
		}
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

		if ($this->retryCounters[$ref] >= $this->maxRetries)
		{
			unset($this->allRequests[$ref], $this->retryCounters[$ref]);
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

		if ($result)
		{
			if ($result->hasError() && $this->isRetryableError($result->error()))
			{
				return $this->onRequestException($result);
			}

			/** @var MultiRequest $request */
			$request = $result->request();
			$this->forgetRequest($request);
		}

		return $result;
	}

	public function abort(MultiResult $result): bool
	{
		$aborted = parent::abort($result);

		if ($aborted)
		{
			/** @var MultiRequest $request */
			$request = $result->request();
			$this->forgetRequest($request);
		}

		return $aborted;
	}

	protected function isRetryableError(?\Throwable $error): bool
	{
		return $error instanceof RequestException
			|| ($error instanceof ResponseException && $error->isServerError());
	}
}