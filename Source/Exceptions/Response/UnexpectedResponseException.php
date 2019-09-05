<?php
namespace Gazelle\Exceptions\Response;


use Gazelle\IResponseData;
use Gazelle\Exceptions\ResponseException;


class UnexpectedResponseException extends ResponseException
{
	public function __construct(IResponseData $data, string $message)
	{
		parent::__construct($data, $message);
	}
}