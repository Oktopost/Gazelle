<?php
namespace Gazelle;


use Gazelle\Utils\IWithCurlOptions;


interface IRequestConfig extends IWithCurlOptions
{
	public function getConnectionTimeout(): float;
	public function getExecutionTimeout(): float;
	public function getMaxRedirects(): int;
	public function getExtraCurlOptions(): array;
	public function hasCurlOptions(): bool;
	
	public function setConnectionTimeout(float $sec): IRequestConfig;
	public function setExecutionTimeout(float $sec, ?float $connectionSec = null): IRequestConfig;
	public function setMaxRedirects(int $max): IRequestConfig;
	
	public function setCurlOption(int $option, $value): IRequestConfig;
	public function setCurlOptions(array $options): IRequestConfig;
	
	public function getParseResponseForErrors(): bool;
	public function setParseResponseForErrors(bool $throw): IRequestConfig;
	
	public function getCurlInfoOptions(): array;
	public function clearCurlInfoOptions(): void;
	
	/**
	 * @param int|int[] $flag
	 */
	public function setCurlInfoOptions($flag): void;
}