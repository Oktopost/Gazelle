<?php
namespace Gazelle;


interface IRequest
{
	public function send(): IResponseData;
	public function queryCode(): int;
	public function queryOK(): bool;
	public function queryHeaders(): array;
	public function queryBody(): string;
	public function queryJSON(): array;
}