<?php
namespace Gazelle;


use Gazelle\Multi\IMultiResult;


interface IMultiConnection
{
	public function poll(): bool;
	public function init(IMultiResult $subject): void;
	public function abort(IMultiResult $subject): void;
	public function next(float $timeout = 0.0): ?IMultiResult;
	public function close(): void;
}