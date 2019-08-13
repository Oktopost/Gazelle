<?php
namespace Gazelle\Exceptions\ServerException;


use Gazelle\Exceptions\Utils\TRequestException;


class GatewayTimeoutException extends GatewayException
{
	use TRequestException;
	
	
	private function getErrorMessage(): string
	{
		return 'Service Unavailable';
	}
}