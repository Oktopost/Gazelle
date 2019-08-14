<?php
namespace Gazelle\Exceptions;


use Gazelle\IResponseData;


class JSONExpectedException extends ResponseException
{
	public function __construct(IResponseData $data)
	{
		parent::__construct($data, 'Response body was not in JSON format');
	}
}