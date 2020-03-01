<?php
namespace Gazelle\Exceptions\Response\ServerException;


use Gazelle\IResponse;


class BadGatewayException extends GatewayException
{
	public function __construct(IResponse $data)
	{
		parent::__construct($data, "{$data->getCode()}: Bad Gateway");
	}
}