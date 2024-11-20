<?php
namespace Gazelle\Multi;


interface IMultiConnection
{
	public function isRunning(): bool;
	public function send(MultiRequest $request, IMultiExecutor $executor): MultiResult;
	public function sendUsing(MultiResult $result): void;
	public function next(float $timeout = 0.1): ?MultiResult;
	public function abort(MultiResult $result): bool;
}