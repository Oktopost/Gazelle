<?php
namespace Gazelle\Tests\Support;


/**
 * Entry point executed directly as a PHP CLI script by RawHttpSocketServer (via proc_open),
 * not autoloaded/instantiated by application code — mirrors how Source/Server/public/index.php
 * is a normal class file that also runs itself when invoked directly.
 *
 * Protocol: reads the raw HTTP response bytes to serve from STDIN, binds an ephemeral TCP
 * port, prints that port number as a single line to STDOUT (so the parent can connect to it),
 * accepts exactly one connection, reads the request headers, writes back the response bytes
 * it was given, then exits.
 */
class RawHttpSocketServerResponder
{
	public static function run(float $acceptTimeout): void
	{
		$response = stream_get_contents(STDIN);

		$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);

		if ($server === false)
		{
			fwrite(STDERR, "bind failed: $errstr");
			exit(1);
		}

		$name = stream_socket_get_name($server, false);
		$port = (int) parse_url('tcp://' . $name, PHP_URL_PORT);

		echo $port . "\n";
		fflush(STDOUT);

		$conn = @stream_socket_accept($server, $acceptTimeout);

		if ($conn === false)
		{
			fwrite(STDERR, 'accept timed out');
			exit(1);
		}

		$request = '';

		while (!str_contains($request, "\r\n\r\n"))
		{
			$chunk = fread($conn, 8192);

			if ($chunk === false || $chunk === '')
			{
				break;
			}

			$request .= $chunk;
		}

		fwrite($conn, $response);
		fclose($conn);
		fclose($server);
	}
}


RawHttpSocketServerResponder::run((float) ($argv[1] ?? 5.0));
