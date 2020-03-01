<?php
namespace Gazelle\Exceptions\Response;


use Gazelle\IResponse;
use Gazelle\Exceptions\ResponseException;


class UnexpectedResponseException extends ResponseException
{
	public function __construct(IResponse $data, string $message)
	{
		parent::__construct($data, $message);
	}
}