<?php
namespace Gazelle\Multi;


interface IMultiExecutor
{
	public function execute(MultiRequest $request): IMultiResult;
	public function abort(IMultiResult $result): bool;
}