<?php
namespace Gazelle\Exceptions\ClientException;


use Gazelle\IResponseData;
use Gazelle\Exceptions\Response\ClientErrorException;


class ForbiddenException extends ClientErrorException
{
	public function __construct(IResponseData $data)
	{
		parent::__construct($data, "{$data->getCode()}: Forbidden");
	}
}