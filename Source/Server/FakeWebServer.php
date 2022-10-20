<?php
namespace Gazelle\Server;

use WebCore\WebResponse;


register_shutdown_function(function()
{
	FakeWebServer::stop();
});


class FakeWebServer
{
	private const PATH_TEMPLATE = '/tmp/fake_web_server_HOST_PORT';
	private const PID_PATH      = self::PATH_TEMPLATE . '.pid';
	private const REQUEST_PATH  = self::PATH_TEMPLATE . '_request';
	private const RESPONSE_PATH = self::PATH_TEMPLATE . '_response';
	
	
	private static $host;
	private static $port;
	
	
	private static function getPath(string $template): string
	{
		if (!self::$host || !self::$port)
			throw new \Exception('Host:port is not set');
		
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
		return file_exists(self::getPidPath()) ? file_get_contents(self::getPidPath()) : null;
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
		self::$host = $host;
		self::$port = $port;
		
		if (self::isRunning())
			return;
		
		$publicPath = realpath(dirname(__FILE__)) . '/public';
		
		$query = 'php -S ' . $host . ':' . $port . ' -t ' . $publicPath . ' &> /dev/null & echo $!';
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
	
	/**
	 * @param WebResponse|array|\stdClass|string|int $response
	 */
	public static function setResponse($response): void
	{
		if (!$response instanceof WebResponse)
		{
			$responseObject = new WebResponse();
			$responseObject->setBody(is_scalar($response) ? $response : jsonencode($response));
			$response = $responseObject;
		}
		
		file_put_contents(self::getPath(self::RESPONSE_PATH), serialize($response));
	}
	
	public static function getLastRequest()
	{
		$file = self::getPath(self::REQUEST_PATH);
		
		if (!file_exists($file))
			return null;
		
		$result = unserialize(jsondecode_std(file_get_contents($file)));
		unlink($file);
		
		return $result;
	}
}