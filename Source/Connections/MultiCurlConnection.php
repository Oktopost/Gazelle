<?php
namespace Gazelle\Connections;


use Gazelle\Multi\IMultiExecutor;
use Gazelle\Response;
use Gazelle\IRequestParams;
use Gazelle\Multi\MultiResult;
use Gazelle\Multi\MultiRequest;
use Gazelle\Multi\IMultiConnection;

use Gazelle\Exceptions\RequestException;
use Gazelle\Exceptions\GazelleException;
use Gazelle\Exceptions\Multi\InitMultiRequestException;
use Gazelle\Exceptions\Multi\UnexpectedCurlHandleException;


class MultiCurlConnection implements IMultiConnection
{
	private ?\CurlMultiHandle	$multiHandle	= null;
	private array				$options		= [];
	
	
	/** @var \CurlHandle[] */
	private array $curls = [];
	
	/** @var MultiResult[] */
	private array $results = [];
	
	
	private function validateResultType($handle): void
	{
		if (!($handle instanceof \CurlHandle))
		{
			if (is_object($handle))
				$name = get_class($handle);
			else
				$name = gettype($handle);
			
			throw new GazelleException("Expecting \CurlHandle. Got $name instead");
		}
	}
	
	private function findRequestForCurlHandle(\CurlHandle $handle): MultiResult
	{
		$index = array_search($handle, $this->curls, true);
		
		if ($index === false)
		{
			throw new UnexpectedCurlHandleException($handle);
		}
		
		$result = $this->results[$index];
		
		array_splice($this->results, $index, 1);
		array_splice($this->curls, $index, 1);
		
		$this->closeCurl($handle);
		
		return $result;
	}
	
	private function closeCurl(\CurlHandle $curlHandle): void
	{
		curl_multi_remove_handle($this->multiHandle, $curlHandle);
		curl_close($curlHandle);
	}
	
	/**
	 * @param \CurlHandle|mixed $handle
	 * @return MultiResult
	 */
	private function handleResult(mixed $handle): MultiResult
	{
		$this->validateResultType($handle);
		$result = $this->findRequestForCurlHandle($handle);
		
		try 
		{
			$response	= $this->toResult($handle, $result->request());
			$error		= null;
		}
		catch (RequestException $re)
		{
			$response	= $re->response();
			$error		= $re;
		}
		catch (\Throwable $t)
		{
			$response	= null;
			$error		= $t;
		}
		
		$result->setResult(
			$response,
			$error
		);
		
		return $result;
	}
	
	private function initCurl(): void
	{
		if ($this->multiHandle)
			return;
		
		$this->multiHandle = curl_multi_init();
		
		foreach ($this->options as $opt => $val) 
		{
			curl_multi_setopt($this->multiHandle, $opt, $val);
		}
	}
	
	
	public function __destruct()
	{
		$this->close();
	}
	
	
	public function toResult(\CurlHandle $handle, IRequestParams $request): Response
	{
		$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		
		// Code 0 means that the connection was not established.
		if ($code == 0)
		{
			$body = false;
		}
		else
		{
			$body = curl_multi_getcontent($handle) ?: ''; 
		}
		
		$response = CurlParser::response($handle, $request, 0, 0, $body);
		
		if ($code != 0)
		{
			$response->setCode($code);
		}
		
		return $response;
	}
	
	
	public function curlMultiHandle(bool $create = false): ?\CurlMultiHandle
	{
		if (!$this->multiHandle && $create)
		{
			$this->initCurl();
		}
		
		return $this->multiHandle;
	}
	
	public function setOptions(array $options): void
	{
		$this->options = $options;
	}
	
	public function getOptions(): array
	{
		return $this->options;
	}
	
	
	public function send(MultiRequest $request, IMultiExecutor $executor): MultiResult
	{
		$result = new MultiResult($request, $executor);
		
		$this->sendUsing($result);
		
		return $result;
	}
	
	public function sendUsing(MultiResult $result): void
	{
		$result->reset();
		
		/** @var MultiRequest $request */
		$request	= $result->request();
		
		$multiCurl	= $this->curlMultiHandle(true);
		$curl		= CurlParser::request(null, $request);
		
		$curlResult = curl_multi_add_handle($multiCurl, $curl);
		
		if ($curlResult != 0)
			throw new InitMultiRequestException($curlResult, $request);
		
		$this->curls[]		= $curl;
		$this->results[]	= $result;
	}
	
	public function next(float $timeout = 0.1): ?MultiResult
	{
		$multiHandle = $this->multiHandle;
		
		if (!$multiHandle)
			return null;
		
		curl_multi_exec($multiHandle, $isRunning);
		
		$info = curl_multi_info_read($this->multiHandle);
		
		if (!$info)
		{
			curl_multi_select($multiHandle, $timeout);			
			$info = curl_multi_info_read($this->multiHandle);
		}
		
		if (!$info)
		{
			return null;
		}
		
		return $this->handleResult($info['handle'] ?? null);
	}
	
	public function abort(MultiResult $result): bool
	{
		if ($result->isExecuted())
			return false;
		
		$index = array_search($result, $this->results, true);
		
		if ($index === false)
			return false;
		
		$this->closeCurl($this->curls[$index]);
		
		array_splice($this->results, $index, 1);
		array_splice($this->curls, $index, 1);
		
		return true;
	}
	
	public function close(): void
	{
		if (!$this->multiHandle)
			return;
		
		foreach ($this->curls as $curl) 
		{
			$this->closeCurl($curl);
		}
		
		$this->results	= [];
		$this->curls	= [];
		
		curl_multi_close($this->multiHandle);
		
		$this->multiHandle = null;
	}
}