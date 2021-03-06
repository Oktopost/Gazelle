<?php
namespace Gazelle\Exceptions\Response\ClientException;


use Gazelle\IResponse;
use Gazelle\Exceptions\Response\ClientErrorException;


class UnauthorizedException extends ClientErrorException
{
	public function __construct(IResponse $data)
	{
		parent::__construct($data, "{$data->getCode()}: Unauthorized");
	}
}