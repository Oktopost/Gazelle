<?php
namespace Gazelle\Exceptions\Response\ServerException;


use Gazelle\IResponseData;


class GatewayTimeoutException extends GatewayException
{
	public function __construct(IResponseData $data)
	{
		parent::__construct($data, "{$data->getCode()}: Service Unavailable");
	}
}