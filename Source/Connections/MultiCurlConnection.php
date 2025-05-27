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
	
	/** @var MultiResult[] */
	private array $completedResults = [];
	
	
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
		$this->closeCurl($handle);
		
		array_splice($this->results, $index, 1);
		array_splice($this->curls, $index, 1);
		
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
			$response = $this->toResult($handle, $result->request());
			$error = null;
		}
		catch (RequestException $re) 
		{
			$response = $re->response();
			$error = $re;
		}
		catch (\Throwable $t) 
		{
			$response = null;
			$error = $t;
		}
		
		$result->setResult($response, $error);
		
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
		$curlError = curl_errno($handle);
		
		if ($curlError !== CURLE_OK)
		{
			$body = false;
		}
		else
		{
			$body = curl_multi_getcontent($handle) ?: ''; 
		}
		
		$totalTime = curl_getinfo($handle, CURLINFO_TOTAL_TIME) ?? 0.0;
		$response = CurlParser::response($handle, $request, 0, $totalTime, $body);
		
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
	
	
	public function isRunning(): bool
	{
		return (bool)($this->curls);
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
		$request = $result->request();
		$multiCurl = $this->curlMultiHandle(true);
		$curl = CurlParser::request(null, $request);
		
		$curlResult = curl_multi_add_handle($multiCurl, $curl);
		
		if ($curlResult != 0)
			throw new InitMultiRequestException($curlResult, $request);
		
		$this->curls[] = $curl;
		$this->results[] = $result;
	}
	
	public function next(float $timeout = 0.1): ?MultiResult
	{
		if (!$this->multiHandle)
			return null;
		
		if ($this->completedResults)
			return array_shift($this->completedResults);
		
		curl_multi_exec($this->multiHandle, $isRunning);
		$results = $this->collectCompletedRequests();
		
		// Handle orphaned requests (curl internal state mismatch)
		if ($isRunning == 0 && $this->curls)
		{
			$this->processOrphanedRequests();
		}
		
		if (!$results && $timeout > 0) 
		{
			curl_multi_select($this->multiHandle, $timeout);
			curl_multi_exec($this->multiHandle, $isRunning);
			$results = $this->collectCompletedRequests();
		}
		
		if ($results) 
		{
			$first = array_shift($results);
			if ($results) 
			{
				array_unshift($this->completedResults, ...$results);
			}
			return $first;
		}
		
		return null;
	}
	
	private function collectCompletedRequests(): array
	{
		$results = [];
		while (($info = curl_multi_info_read($this->multiHandle)) !== false) 
		{
			if ($info['msg'] === CURLMSG_DONE) 
			{
				$results[] = $this->handleResult($info['handle'] ?? null);
			}
		}
		
		return $results;
	}
	
	private function processOrphanedRequests(): void
	{
		foreach ($this->curls as $i => $curl) 
		{
			$result = $this->results[$i];
			$errno = curl_errno($curl);
			$error = curl_error($curl) ?: 'Request failed or timed out';
			
			try 
			{
				$response = $this->toResult($curl, $result->request());
			}
			catch (\Throwable $t) 
			{
				$response = null;
				$error = "Failed to process response: " . $t->getMessage();
			}
			
			$result->setResult($response, new \Exception($error, $errno));
			$this->completedResults[] = $result;
		}
		
		// Clean up orphaned handles
		foreach ($this->curls as $curl) 
		{
			curl_multi_remove_handle($this->multiHandle, $curl);
			curl_close($curl);
		}
		
		$this->curls = [];
		$this->results = [];
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
		$this->completedResults = [];
		
		curl_multi_close($this->multiHandle);
		
		$this->multiHandle = null;
	}
}