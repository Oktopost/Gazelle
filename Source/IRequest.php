<?php
namespace Gazelle;


use Gazelle\Exceptions\GazelleException;


interface IRequest extends IRequestParams
{
	public function get(): IResponseData;
	public function put(): IResponseData;
	public function post(): IResponseData;
	public function head(): IResponseData;
	public function delete(): IResponseData;
	public function options(): IResponseData;
	public function patch(): IResponseData;
	
	public function tryGet(): ?IResponseData;
	public function tryPut(): ?IResponseData;
	public function tryPost(): ?IResponseData;
	public function tryHead(): ?IResponseData;
	public function tryDelete(): ?IResponseData;
	public function tryOptions(): ?IResponseData;
	public function tryPatch(): ?IResponseData;
	
	public function send(): IResponseData;
	public function queryCode(): int;
	public function queryOK(): bool;
	public function queryHeaders(): array;
	public function queryBody(): string;
	public function queryJSON(): array;
	
	public function trySend(): ?IResponseData;
	public function tryQueryCode(): ?int;
	public function tryQueryOK(): bool;
	public function tryQueryHeaders(bool $defaultAsEmptyArray = false): ?array;
	public function tryQueryBody(bool $defaultAsEmptyString = false): ?string;
	public function tryQueryJSON(): ?array;
	
	public function getLastException(): ?GazelleException;
	public function hasError(): bool;
	public function close(): void;
}