<?php
namespace Gazelle\Exceptions\Request;


use Gazelle\IRequestParams;
use Gazelle\Exceptions\RequestException;


class UnhandledCurlException extends RequestException
{
	public function __construct(\CurlHandle $resource, IRequestParams $requestData)
	{
		parent::__construct($requestData, curl_error($resource), curl_errno($resource));
	}
}