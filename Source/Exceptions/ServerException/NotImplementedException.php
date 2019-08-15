<?php
namespace Gazelle\Exceptions\ServerException;


use Gazelle\Exceptions\Response\ServerErrorException;
use Gazelle\Exceptions\Utils\TRequestException;


class NotImplementedException extends ServerErrorException
{
	use TRequestException;
	
	
	private function getErrorMessage(): string
	{
		return 'Not Implemented';
	}
}