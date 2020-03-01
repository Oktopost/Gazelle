<?php
namespace Gazelle\Exceptions\Response\ServerException;


use Gazelle\IResponse;
use Gazelle\Exceptions\Response\ServerErrorException;


class NetworkReadTimeoutException extends ServerErrorException
{
	public function __construct(IResponse $data)
	{
		parent::__construct($data, "{$data->getCode()}: Network Read Timeout");
	}
}