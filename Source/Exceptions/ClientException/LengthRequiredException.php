<?php
namespace Gazelle\Exceptions\ClientException;


use Gazelle\IResponseData;
use Gazelle\Exceptions\ClientErrorException;


class LengthRequiredException extends ClientErrorException
{
	public function __construct(IResponseData $data)
	{
		parent::__construct($data, "{$data->getCode()}: Length Required");
	}
}