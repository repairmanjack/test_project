<?php 

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;
use Test\Models\User;
use Test\Models\Transaction;
use Test\Render;

chdir(dirname(__DIR__));
require 'vendor/autoload.php';
require 'dbconfig.php';

$request = ServerRequestFactory::fromGlobals();
$path = $request->getUri()->getPath();
$action = null;

try {
	$user = User::auth($request);
	if(!$user->isLogged()) {
		$action = function(ServerRequestInterface $request) {
			return new HtmlResponse(file_get_contents('Test/View/auth.tpl'));
		};
	} elseif($path == '/') {
		$request = $request->withAttribute('user', $user);
		$action = function(ServerRequestInterface $request) {
			$user = $request->getAttribute('user');
			return new HtmlResponse(Render::render('Test/View/index.tpl', compact('user')));
		};
	} elseif($path == '/pay') {
		$paramList = $request->getParsedBody();
		$sum = 0;
		if(isset($paramList['sum'])) {
			$sum = round(abs(floatval($paramList['sum'])),2);
		}
		$request = $request->withAttribute('user', $user);
		$request = $request->withAttribute('sum', $sum);
		$action = function(ServerRequestInterface $request) {
			$user = $request->getAttribute('user');
			$sum = $request->getAttribute('sum');
            $tr = new Transaction;
            $tr->setSum($sum);
            $tr->setUserId($user->getId());
            $tr->setDateTime(date('Y-m-d H:i:s'));
            $tr->save();
            return (new HtmlResponse(''))->withHeader('Location', '/');
		};
	}

	if($action) {
		$response = $action($request);
	} else {
		$response = new HtmlResponse("Страница не найдена", 404);
	}

} catch(Exception $e) {
    $error = $e->getMessage();
    $response = new HtmlResponse(Render::render('Test/View/error.tpl', compact('error')));
}


$emitter = new SapiEmitter();
$emitter->emit($response);