<?php
namespace Gazelle\Multi;


interface IMultiConnection
{
	public function send(MultiRequest $request, IMultiExecutor $executor): MultiResult;
	public function next(float $timeout = 0.1): ?MultiResult;
	public function abort(MultiResult $result): bool;
}