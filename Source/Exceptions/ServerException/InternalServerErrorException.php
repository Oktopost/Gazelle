<?php
namespace Gazelle\Exceptions\ServerException;


use Gazelle\Exceptions\Response\ServerErrorException;
use Gazelle\Exceptions\Utils\TRequestException;


class InternalServerErrorException extends ServerErrorException
{
	use TRequestException;
	
	
	private function getErrorMessage(): string
	{
		return 'Internal Server Error';
	}
}