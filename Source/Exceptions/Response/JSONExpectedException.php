<?php
namespace Gazelle\Exceptions\Response;


use Gazelle\IResponseData;
use Gazelle\Exceptions\ResponseException;


class JSONExpectedException extends ResponseException
{
	public function __construct(IResponseData $data)
	{
		parent::__construct($data, 'Response body was not in JSON format');
	}
}