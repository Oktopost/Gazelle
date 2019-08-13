<?php
namespace Gazelle\Exceptions\ServerException;


use Gazelle\Exceptions\Utils\TRequestException;


class BadGatewayException extends GatewayException
{
	use TRequestException;
	
	
	private function getErrorMessage(): string
	{
		return 'Bad Gateway';
	}
}