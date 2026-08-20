<?php
namespace Gazelle;


use Traitor\TStaticClass;
use Gazelle\Multi\IMultiConnection;


class GazelleMock
{
	use TStaticClass;
	
	
	public static ?IMultiConnection	$multiConnection	= null;
	public static ?IConnection		$connection			= null;
	
	
	public static function reset(): void
	{
		self::$multiConnection	= null;
		self::$connection		= null;
	}
}