<?php
namespace Gazelle;


use Gazelle\Utils\ICurlOptions;


interface IRequestConfig extends ICurlOptions
{
	public function getConnectionTimeout(): float;
	public function getExecutionTimeout(): float;
	public function getMaxRedirects(): int;
	public function getCurlOptions(): array;
	public function hasCurlOptions(): bool;
	
	public function setConnectionTimeout(float $sec): IRequestConfig;
	public function setExecutionTimeout(float $sec, ?float $connectionSec = null): IRequestConfig;
	public function setMaxRedirects(int $max): IRequestConfig;
	
	public function setCurlOption(int $option, $value): IRequestConfig;
	public function setCurlOptions(array $options): IRequestConfig;
}