<?php
namespace Gazelle;


interface IRequest
{
	public function get(): IResponseData;
	public function put(): IResponseData;
	public function post(): IResponseData;
	public function head(): IResponseData;
	public function delete(): IResponseData;
	public function options(): IResponseData;
	public function patch(): IResponseData;
	
	public function send(): IResponseData;
	public function queryCode(): int;
	public function queryOK(): bool;
	public function queryHeaders(): array;
	public function queryBody(): string;
	public function queryJSON(): array;
}