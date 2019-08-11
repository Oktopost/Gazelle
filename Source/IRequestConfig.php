<?php
namespace Gazelle;


interface IRequestConfig
{
	public function getConnectionTimeout(): float;
	public function getRequestTimeout(): float;
	public function getMaxRedirects(): float;
	
	public function setTimeout(float $connectionSec, ?int $requestSec = null): void;
	public function setConnectionTimeout(float $sec): void;
	public function setRequestTimeout(float $sec): void;
	public function setMaxRedirects(int $max): void;
}