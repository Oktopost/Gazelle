<?php
namespace Gazelle\Exceptions\Response\ServerException;


use Gazelle\IResponse;


class GatewayTimeoutException extends GatewayException
{
	public function __construct(IResponse $data)
	{
		parent::__construct($data, "{$data->getCode()}: Service Unavailable");
	}
}