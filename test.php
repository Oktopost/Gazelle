<?php
use Gazelle\Gazelle;

require_once __DIR__ . '/vendor/autoload.php';


$gazelle = new Gazelle();

$a = $gazelle->request('http://www.oktoposadasdasst.com');

try
{
	$result = $a->get();
}
catch (\Throwable $t)
{
	echo 1;
}
