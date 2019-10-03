<?php
require_once '../../../vendor/autoload.php';


use WebCore\WebRequest;
use WebCore\WebResponse;


class Server
{
	
	private const PATH_TEMPLATE = '/tmp/fake_web_server_HOST_PORT';
	private const REQUEST_PATH = self::PATH_TEMPLATE . '_request';
	private const RESPONSE_PATH = self::PATH_TEMPLATE . '_response';
	
	
	
	function getPath(string $template): string
	{
		$host = $_SERVER['SERVER_NAME'];
		$port = $_SERVER['SERVER_PORT'];
		
		$result = str_replace('HOST', $host, $template);
		$result = str_replace('PORT', $port, $result);
		
		return $result;
	}


	public function __construct()
	{
		$request = WebRequest::current();
		
		$requestData = [
			'headers'	=> $request->getHeaders(),
			'params'	=> $request->getParams(),
			'method'	=> $request->getMethod(),
			'url'		=> $request->getURL(),
			'uri'		=> $request->getURI()
		];
		
		file_put_contents($this->getPath(self::REQUEST_PATH), jsonencode(serialize($requestData)));
		
		if (file_exists($this->getPath(self::RESPONSE_PATH)))
		{
			/** @var WebResponse $response */
			$response = unserialize(file_get_contents($this->getPath(self::RESPONSE_PATH)));
			$response->apply();
		}
		else
		{
			$response = new WebResponse();
			$response->setCode(500);
			$response->apply();
		}
	}
}

new Server();