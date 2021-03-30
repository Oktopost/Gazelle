<?php
namespace Gazelle;


interface IResponse
{
	public function getRequestParams(): IRequestParams;
	public function requestMetaData(): IRequestMetaData;
	
	public function getCode(): int;
	
	public function getHeaders(): array;
	public function getHeader(string $key, bool $caseSensitive = false): ?string;
	public function hasHeader(string $key): bool;
	
	public function hasBody(): bool;
	public function bodyLength(): int;
	public function getBody(): string;
	public function getJSON(): array;
	public function tryGetJSON(): ?array;
	
	public function isSuccessful(): bool;
	public function isComplete(): bool;
	public function isRedirect(): bool;
	public function isFailed(): bool;
	
	public function isServerError(): bool;
	public function isClientError(): bool;
}