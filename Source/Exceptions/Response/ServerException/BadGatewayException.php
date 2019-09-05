<?php
namespace Gazelle\Exceptions\Response\ServerException;


use Gazelle\IResponseData;


class BadGatewayException extends GatewayException
{
	public function __construct(IResponseData $data)
	{
		parent::__construct($data, "{$data->getCode()}: Bad Gateway");
	}
}