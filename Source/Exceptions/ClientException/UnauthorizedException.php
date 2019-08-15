<?php
namespace Gazelle\Exceptions\ClientException;


use Gazelle\IResponseData;
use Gazelle\Exceptions\Response\ClientErrorException;


class UnauthorizedException extends ClientErrorException
{
	public function __construct(IResponseData $data)
	{
		parent::__construct($data, "{$data->getCode()}: Unauthorized");
	}
}