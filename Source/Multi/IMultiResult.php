<?php
namespace Gazelle\Multi;


use Gazelle\IResponse;
use Gazelle\IRequestParams;


interface IMultiResult
{
	public function request(): IRequestParams;
	public function response(): ?IResponse;
	public function error(): ?\Throwable;
	
	public function isExecuted(): bool;
	public function hasError(): bool;
	
	public function abort(): bool;
	public function onComplete(callable $callback): void;
}