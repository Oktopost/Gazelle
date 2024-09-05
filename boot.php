<?php
require_once __DIR__ . '/vendor/autoload.php';


use Gazelle\Gazelle;
use Gazelle\Server\FakeWebServer;
use WebServer\Response;


FakeWebServer::start();
FakeWebServer::setResponse(Response::OK());

$request = (new Gazelle())->request('http://localhost:8080?a=a&a=b');
$request->setBody('foo-bar');


$response = $request->get();
var_dump(FakeWebServer::getLastRequest()->getURL());