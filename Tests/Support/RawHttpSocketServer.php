<?php
namespace Gazelle\Tests\Support;


/**
 * A single-shot, real HTTP responder for tests that need CurlConnection to parse
 * a genuine HTTP/1.1 status line + header block (something file:// can never produce,
 * since curl reports CURLINFO_HEADER_SIZE=0 / CURLINFO_RESPONSE_CODE=0 for that protocol).
 *
 * This is intentionally NOT built for concurrent/multiple requests: it spawns
 * RawHttpSocketServerResponder as a fresh PHP subprocess (via proc_open, the same technique
 * already used in IPProviderTest), which binds one ephemeral port, accepts exactly one
 * connection, writes back the one canned response it was given (received over STDIN), and
 * exits. Running the responder as a separate OS process (rather than a same-process
 * non-blocking loop) is what makes it usable with CurlConnection's blocking curl_exec() —
 * the parent can block on the request while the child independently accepts and answers it.
 * Each instance handles one request. Multiple independent instances can be used together
 * to test concurrent MultiCurlConnection requests.
 */
class RawHttpSocketServer
{
	/** @var resource */
	private $process;

	/** @var resource[] */
	private array $pipes;

	private int $port;


	public function __construct(string $rawHttpResponse, float $acceptTimeout = 5.0)
	{
		$responderPath = __DIR__ . '/RawHttpSocketServerResponder.php';

		$process = proc_open(
			[PHP_BINARY, '-n', $responderPath, (string) $acceptTimeout],
			[['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
			$pipes
		);

		if (!is_resource($process))
		{
			throw new \RuntimeException('Failed to start RawHttpSocketServer subprocess');
		}

		$this->process = $process;
		$this->pipes = $pipes;

		fwrite($this->pipes[0], $rawHttpResponse);
		fclose($this->pipes[0]);

		$line = fgets($this->pipes[1]);

		if ($line === false || !ctype_digit(trim($line)))
		{
			$stderr = stream_get_contents($this->pipes[2]);
			$this->close();

			throw new \RuntimeException(
				"RawHttpSocketServer failed to report a port. stdout: " . var_export($line, true) .
				", stderr: $stderr"
			);
		}

		$this->port = (int) trim($line);
	}

	public function port(): int
	{
		return $this->port;
	}

	public static function buildResponse(int $status, string $reason, array $headers, string $body): string
	{
		$lines = ["HTTP/1.1 $status $reason"];

		foreach ($headers as $name => $value)
		{
			$lines[] = "$name: $value";
		}

		return implode("\r\n", $lines) . "\r\n\r\n" . $body;
	}

	public function close(): void
	{
		if (isset($this->pipes))
		{
			foreach ($this->pipes as $pipe)
			{
				if (is_resource($pipe))
				{
					fclose($pipe);
				}
			}
		}

		if (isset($this->process) && is_resource($this->process))
		{
			proc_terminate($this->process);
			proc_close($this->process);
		}
	}

	public function __destruct()
	{
		$this->close();
	}
}
