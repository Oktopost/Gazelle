<?php
namespace Gazelle\Exceptions\Response\Unexpected;


use Gazelle\IResponse;

class MissingJSONFieldException extends InvalidJSONResponseException
{
	public function __construct(IResponse $data, $field)
	{
		parent::__construct($data, "Body missing the field $field");
	}
}