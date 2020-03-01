<?php
namespace Gazelle;


use Gazelle\Exceptions\GazelleException;


interface IRequest extends IRequestParams
{
	public function get(): IResponse;
	public function put(): IResponse;
	public function post(): IResponse;
	public function head(): IResponse;
	public function delete(): IResponse;
	public function options(): IResponse;
	public function patch(): IResponse;
	
	public function tryGet(): ?IResponse;
	public function tryPut(): ?IResponse;
	public function tryPost(): ?IResponse;
	public function tryHead(): ?IResponse;
	public function tryDelete(): ?IResponse;
	public function tryOptions(): ?IResponse;
	public function tryPatch(): ?IResponse;
	
	public function send(): IResponse;
	public function queryCode(): int;
	public function queryOK(): bool;
	public function queryHeaders(): array;
	public function queryBody(): string;
	public function queryJSON(): array;
	
	public function trySend(): ?IResponse;
	public function tryQueryCode(): ?int;
	public function tryQueryOK(): bool;
	public function tryQueryHeaders(bool $defaultAsEmptyArray = false): ?array;
	public function tryQueryBody(bool $defaultAsEmptyString = false): ?string;
	public function tryQueryJSON(): ?array;
	
	public function getLastException(): ?GazelleException;
	public function hasError(): bool;
	public function close(): void;
}