<?php
namespace Gazelle\Exceptions\Request;


use Gazelle\IRequestParams;
use Gazelle\Exceptions\RequestException;


class UnhandledCurlException extends RequestException
{
	/**
	 * @param resource $resource
	 * @param IRequestParams $requestData
	 */
	public function __construct($resource, IRequestParams $requestData)
	{
		parent::__construct($requestData, curl_error($resource), curl_errno($resource));
	}
}