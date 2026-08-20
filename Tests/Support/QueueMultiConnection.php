<?php
namespace Gazelle\Tests\Support;


use Gazelle\IResponse;
use Gazelle\Multi\IMultiConnection;
use Gazelle\Multi\IMultiExecutor;
use Gazelle\Multi\MultiRequest;
use Gazelle\Multi\MultiResult;


class QueueMultiConnection implements IMultiConnection
{
	/** @var MultiResult[] */
	public array $sent = [];

	/** @var MultiResult[] */
	private array $ready = [];

	public int $sendUsingCalls = 0;
	public bool $closed = false;


	public function send(MultiRequest $request, IMultiExecutor $executor): MultiResult
	{
		$result = new MultiResult($request, $executor);
		$this->sent[] = $result;
		return $result;
	}

	public function sendUsing(MultiResult $result): void
	{
		$this->sendUsingCalls++;
		$result->reset();
	}

	public function next(float $timeout = 0.1): ?MultiResult
	{
		return array_shift($this->ready);
	}

	public function abort(MultiResult $result): bool
	{
		$index = array_search($result, $this->sent, true);
		if ($index === false)
		{
			return false;
		}

		array_splice($this->sent, $index, 1);
		return true;
	}

	public function complete(MultiResult $result, ?IResponse $response = null, ?\Throwable $error = null): void
	{
		$result->setResult($response, $error);
		$this->ready[] = $result;
	}

	public function close(): void
	{
		$this->closed = true;
		$this->sent = [];
		$this->ready = [];
	}
}
