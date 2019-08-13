<?php
namespace Gazelle\Exceptions\ClientException;


use Gazelle\IResponseData;
use Gazelle\Exceptions\ClientErrorException;


class NotFoundException extends ClientErrorException
{
	public function __construct(IResponseData $data)
	{
		parent::__construct($data, "{$data->getCode()}: Not Found");
	}
}