<?php
namespace Gazelle\Exceptions\Response\Unexpected;


use Gazelle\IResponse;
use Gazelle\Exceptions\Response\UnexpectedResponseException;


class InvalidJSONResponseException extends UnexpectedResponseException
{
	public function __construct(IResponse $data, string $message = 'Response body is not JSON')
	{
		parent::__construct($data, $message);
	}
}