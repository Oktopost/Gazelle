<?php
namespace Gazelle\Exceptions\ServerException;


use Gazelle\Exceptions\Response\ServerErrorException;
use Gazelle\Exceptions\Utils\TRequestException;


class ServiceUnavailableException extends ServerErrorException
{
	use TRequestException;
	
	
	private function getErrorMessage(): string
	{
		return 'Service Unavailable';
	}
}