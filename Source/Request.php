<?php
namespace Gazelle;


class Request extends RequestData implements IRequest
{
	/** @var IRequestConfig */
	private $config;
	
	
	private function __construct(IRequestConfig $config)
	{
		$this->config = $config;
	}
	
	
	public function __clone()
	{
		$this->config = clone $this->config;
	}
	
	
	public function get(): IResponseData
	{
		// TODO: Implement get() method.
	}
	
	public function put(): IResponseData
	{
		// TODO: Implement put() method.
	}
	
	public function post(): IResponseData
	{
		// TODO: Implement post() method.
	}
	
	public function head(): IResponseData
	{
		// TODO: Implement head() method.
	}
	
	public function delete(): IResponseData
	{
		// TODO: Implement delete() method.
	}
	
	public function options(): IResponseData
	{
		// TODO: Implement options() method.
	}
	
	public function patch(): IResponseData
	{
		// TODO: Implement patch() method.
	}
	
	public function send(): IResponseData
	{
		// TODO: Implement send() method.
	}
	
	public function queryCode(): int
	{
		// TODO: Implement queryCode() method.
	}
	
	public function queryOK(): bool
	{
		// TODO: Implement queryOK() method.
	}
	
	public function queryHeaders(): array
	{
		// TODO: Implement queryHeaders() method.
	}
	
	public function queryBody(): string
	{
		// TODO: Implement queryBody() method.
	}
	
	public function queryJSON(): array
	{
		// TODO: Implement queryJSON() method.
	}
}