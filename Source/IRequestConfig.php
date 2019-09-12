<?php
namespace Gazelle;


use Gazelle\Utils\IWithCurlOptions;


interface IRequestConfig extends IWithCurlOptions
{
	public function getConnectionTimeout(): float;
	public function getExecutionTimeout(): float;
	public function getMaxRedirects(): int;
	public function getCurlOptions(): array;
	public function hasCurlOptions(): bool;
	public function getParseResponseForErrors(): bool;
	public function getCurlInfoOptions(): array;
	public function getIsConnectionReused(): bool;
}