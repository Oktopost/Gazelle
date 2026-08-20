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


	protected function sleepBeforeRetry(int $seconds): void
	{
		sleep($seconds);
	}

	private function retry(): void
	{
		if ($this->retryIteration >= $this->getMaxRetries())
			return;

		$this->retryIteration++;

		$this->sleepBeforeRetry($this->retryIteration);

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
		if ($this->retryIteration >= $this->getMaxRetries())
			return $result;

		$this->toRetry[] = $result;

		return null;
	}


	public function send(MultiRequest $request, IMultiExecutor $executor): MultiResult
	{
		$this->requestsToProcess++;
		return $this->child()->send($request, $executor);
	}

	public function next(float $timeout = 0.1): ?MultiResult
	{
		$result = $this->child()->next($timeout);

		if ($result)
		{
			$this->requestsToProcess--;

			if ($result->hasError() && $this->isRetryableError($result->error()))
			{
				$result = $this->onRequestException($result);
			}
		}

		$this->maybeStartRetryBatch();

		return $result;
	}

	public function abort(MultiResult $result): bool
	{
		$aborted = parent::abort($result);

		if ($aborted)
		{
			$this->requestsToProcess--;

			$this->maybeStartRetryBatch();
		}

		return $aborted;
	}

	private function maybeStartRetryBatch(): void
	{
		if ($this->requestsToProcess != 0)
			return;

		if ($this->toRetry)
		{
			$this->retry();
			return;
		}

		$this->retryIteration = 0;
	}
}
