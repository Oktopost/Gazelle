<?php
namespace Gazelle;


interface IResponseData
{
	public function requestData(): IRequestData;
	public function requestConfig(): IRequestConfig;
	
	public function getCode(): int;
	
	public function getHeaders(): array;
	public function getHeader(string $key, bool $firstValue = true): ?string;
	public function hasHeader(string $key): bool;
	
	public function hasBody(): bool;
	public function bodyLength(): int;
	public function getBody(): string;
	public function getJSON(): ?array;
	
	public function isSuccessful(): bool;
	public function isRedirect(): bool;
	public function isFailed(): bool;
}