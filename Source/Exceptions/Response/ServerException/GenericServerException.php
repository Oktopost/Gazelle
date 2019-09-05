<?php
namespace Gazelle\Exceptions\Response\ServerException;


use Gazelle\Exceptions\Response\ServerErrorException;
use Gazelle\IResponseData;


class GenericServerException extends ServerErrorException
{
	private function getErrorMessage(int $code): string
	{
		switch ($code)
		{
			case 505:
				return 'HTTP Version Not Supported';
			
			case 506:
				return 'Variant Also Negotiates';
			
			case 507:
				return 'Insufficient Storage';
			
			case 508:
				return 'Insufficient Storage';
				
			case 509:
				return 'Bandwidth Limit Exceeded';
			
			case 510:
				return 'Not Extended';
			
			case 511:
				return 'Network Authentication Required';
				
			default:
				return 'Unrecognized Server Error';
		}
	}
	
	
	public function __construct(IResponseData $data)
	{
		$code = $data->getCode();
		parent::__construct($data, "$code: {$this->getErrorMessage($code)}");
	}
}