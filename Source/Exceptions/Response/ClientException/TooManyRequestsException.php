<?php
namespace Gazelle\Exceptions\Response\ClientException;


use Gazelle\IResponse;
use Gazelle\Exceptions\Response\ClientErrorException;


class TooManyRequestsException extends ClientErrorException
{
	public function __construct(IResponse $data)
	{
		parent::__construct($data, "{$data->getCode()}: Too Many Requests");
	}
}