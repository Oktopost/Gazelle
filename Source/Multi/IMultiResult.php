<?php
namespace Gazelle\Multi;


use Gazelle\IResponse;
use Gazelle\IRequestParams;


interface IMultiResult
{
	public function request(): IRequestParams;
	public function response(): ?IResponse;
	public function metadata(): mixed;
	public function error(): ?\Throwable;
	public function isExecuted(): bool;
	public function hasError(): bool;
	public function hasMetadata(): bool;
	public function abort(): bool;
	public function onComplete(callable $callback): void;
}