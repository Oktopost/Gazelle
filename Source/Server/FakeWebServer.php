<?php
namespace Gazelle\Server;


use WebCore\WebResponse;
use WebCore\IWebResponse;


class FakeWebServer
{
	private const PATH_TEMPLATE = '/tmp/fake_web_server_HOST_PORT';
	private const PID_PATH      = self::PATH_TEMPLATE . '.pid';
	private const REQUEST_PATH  = self::PATH_TEMPLATE . '_request';
	private const RESPONSE_PATH = self::PATH_TEMPLATE . '_response';
	
	
	private static bool $isShutdownRegistered = false;
	
	private static string	$host = "";
	private static int		$port	= -1;
	
	
	private static function initShutdown(): void
	{
		if (self::$isShutdownRegistered)
			return;
		
		self::$isShutdownRegistered = true;
		
		register_shutdown_function(function()
		{
			FakeWebServer::stop();
		});
	}
	
	private static function getPath(string $template): string
	{
		if (!self::$host || !self::$port)
			return "";
		
		$result = str_replace('HOST', self::$host, $template);
		$result = str_replace('PORT', self::$port, $result);
		
		return $result;
	}
	
	
	private static function getPidPath(): string
	{
		return self::getPath(self::PID_PATH);
	}
	
	
	private static function getPid(): ?string
	{
		$path = self::getPidPath();
		
		if (!$path)
			return null;
		
		return file_exists($path) ? file_get_contents(self::getPidPath()) : null;
	}
	
	private static function setPid(int $pId): void
	{
		file_put_contents(self::getPidPath(), $pId);
	}
	
	
	public static function isRunning(): bool
	{
		$pid = self::getPid();
		
		if (!$pid)
			return false;
		
		$output = [];
		exec("ps -A | grep -i $pid | grep -v grep", $output);
		
		$result = count($output) > 0;
		
		if (!$result)
			unlink(self::getPidPath());
		
		return $result;
	}
	
	public static function start(string $host = 'localhost', int $port = 8080): void
	{
		if (self::isRunning())
		{
			if (self::$host == $host && self::$port == $port)
			{
				return;
			}
			else
			{
				self::stop();
			}
		}
		
		self::initShutdown();
		
		self::$host = $host;
		self::$port = $port;
		
		$publicPath = realpath(dirname(__FILE__)) . '/public';
		
		$query = 'php -S ' . $host . ':' . $port . ' -t ' . $publicPath . ' > /dev/null 2>&1 & echo $!';
		self::setPid(exec($query));
		
		// server need some time to start
		sleep(1);
	}
	
	public static function stop(): void
	{
		if (file_exists(self::getPath(self::REQUEST_PATH)))
			unlink(self::getPath(self::REQUEST_PATH));
		
		if (file_exists(self::getPath(self::RESPONSE_PATH)))
			unlink(self::getPath(self::RESPONSE_PATH));
		
		if (!self::isRunning())
			return;
		
		exec('kill -9 ' . self::getPid());
		unlink(self::getPidPath());
	}
	
	public static function setResponse(IWebResponse|array|\stdClass|string|int $response): void
	{
		if (!$response instanceof IWebResponse)
		{
			$responseObject = new WebResponse();
			$responseObject->setBody(is_scalar($response) ? $response : jsonencode($response));
			$response = $responseObject;
		}
		
		file_put_contents(self::getPath(self::RESPONSE_PATH), serialize($response));
	}
	
	public static function cleanupRequest(): void
	{
		$file = self::getPath(self::REQUEST_PATH);
		
		if (file_exists($file))
		{
			unlink($file);
		}
	}
	
	public static function getLastRequest(): ?ServerWebRequest
	{
		$file = self::getPath(self::REQUEST_PATH);
		
		if (!file_exists($file))
			return null;
		
		$result = ServerWebRequest::fromArray(jsondecode_a(file_get_contents($file)));
		unlink($file);
		
		return $result;
	}
}