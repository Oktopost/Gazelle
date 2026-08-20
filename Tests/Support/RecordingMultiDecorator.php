<?php
namespace Gazelle\Tests\Support;


use Gazelle\AbstractMultiDecorator;
use Gazelle\Multi\IMultiExecutor;
use Gazelle\Multi\MultiRequest;
use Gazelle\Multi\MultiResult;


class RecordingMultiDecorator extends AbstractMultiDecorator
{
	public function __construct(
		private \ArrayObject $events,
		private string $name
	) {}

	public function send(MultiRequest $request, IMultiExecutor $executor): MultiResult
	{
		$this->events[] = $this->name;
		return $this->child()->send($request, $executor);
	}

	public function next(float $timeout = 0.1): ?MultiResult
	{
		return $this->child()->next($timeout);
	}
}
