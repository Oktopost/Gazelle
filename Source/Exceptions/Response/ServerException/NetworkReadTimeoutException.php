<?php
namespace Gazelle\Exceptions\Response\ServerException;


use Gazelle\IResponseData;
use Gazelle\Exceptions\Response\ServerErrorException;


class NetworkReadTimeoutException extends ServerErrorException
{
	public function __construct(IResponseData $data)
	{
		parent::__construct($data, "{$data->getCode()}: Network Read Timeout");
	}
}