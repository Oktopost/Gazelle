<?php
namespace Gazelle\Exceptions\Response\ClientException;


use Gazelle\IResponseData;
use Gazelle\Exceptions\Response\ClientErrorException;


class MethodNotAllowedException extends ClientErrorException
{
	public function __construct(IResponseData $data)
	{
		parent::__construct($data, "{$data->getCode()}: Method Not Allowed");
	}
}