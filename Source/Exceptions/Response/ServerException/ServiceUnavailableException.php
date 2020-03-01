<?php
namespace Gazelle\Exceptions\Response\ServerException;


use Gazelle\IResponse;
use Gazelle\Exceptions\Response\ServerErrorException;


class ServiceUnavailableException extends ServerErrorException
{
	public function __construct(IResponse $data)
	{
		parent::__construct($data, "{$data->getCode()}: Service Unavailable");
	}
}