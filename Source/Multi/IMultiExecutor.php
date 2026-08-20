<?php
namespace Gazelle\Multi;


interface IMultiExecutor
{
	public function execute(MultiRequest $request): MultiResult;
	public function abort(MultiResult $result): bool;
}