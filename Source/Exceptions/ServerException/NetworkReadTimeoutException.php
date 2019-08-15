<?php
namespace Gazelle\Exceptions\ServerException;


use Gazelle\Exceptions\Response\ServerErrorException;
use Gazelle\Exceptions\Utils\TRequestException;


class NetworkReadTimeoutException extends ServerErrorException
{
	use TRequestException;
	
	
	private function getMessageError(): string
	{
		return 'Network Read Timeout';
	}
}