<?php
namespace Gazelle\Exceptions\Response\Unexpected;


use Gazelle\IResponseData;
use Gazelle\Exceptions\Response\UnexpectedResponseException;


class InvalidJSONResponseException extends UnexpectedResponseException
{
	public function __construct(IResponseData $data)
	{
		parent::__construct($data, 'Response body is not JSON');
	}
}