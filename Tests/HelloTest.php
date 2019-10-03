<?php
require_once '../vendor/autoload.php';


use Gazelle\Gazelle;
use Gazelle\Server\FakeWebServer;


FakeWebServer::start('localhost', 8080);
FakeWebServer::setResponse(['foo' => 'bar']);

$request = (new Gazelle())->request('http://localhost:8080?a=a&a=b');
$request->setBody('foo-bar');

$response = $request->get();
var_dump(FakeWebServer::getLastRequest());

//FakeWebServer::stop(); // unnecessary