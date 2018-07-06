<?php 

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;
use Test\Models\User;
use Test\Models\Transaction;
use Test\Render;

require 'vendor/autoload.php';
require 'dbconfig.php';

$request = ServerRequestFactory::fromGlobals();
$path = $request->getUri()->getPath();
$action = null;

try {
	$user = User::auth($request);
	if(!$user->isLogged()) {
		$action = function(ServerRequestInterface $request) {
			return new HtmlResponse(file_get_contents('Test/View/auth.html'));
		};
	} elseif($path == '/') {
		$request = $request->withAttribute('user', $user);
		$action = function(ServerRequestInterface $request) {
			$user = $request->getAttribute('user');
			return new HtmlResponse(Render::render('Test/View/index.html', compact('user')));
		};
	} elseif($path == '/pay') {
		$paramList = $request->getParsedBody();
		$sum = 0;
		if(isset($paramList['sum'])) {
			$sum = abs(floatval($paramList['sum']));
		}
		$request = $request->withAttribute('user', $user);
		$request = $request->withAttribute('sum', $sum);
		$action = function(ServerRequestInterface $request) {
			$user = $request->getAttribute('user');
			$sum = $request->getAttribute('sum');
			$userSum = $user->getTransactionSum();
			$error = null;
			if(($userSum-$sum)<0) {
				$error = "На вашем счёте не достаточно средств для списания суммы {$sum} руб. Текущий остаток: {$userSum} руб.";
			} elseif(!$sum) {
				$error = "Сумма транзакции не может быть равна 0";
			}
			if(!is_null($error)) {
				return new HtmlResponse(Render::render('Test/View/error.html', compact('error')));
			} else {
				$tr = new Transaction;
				$tr->setSum($sum);
				$tr->setUserId($user->getId());
				$tr->setDateTime(date('Y-m-d H:i:s'));
				$tr->save();
				return (new HtmlResponse(''))->withHeader('Location', '/');
			}
			
		};
	}

	if($action) {
		$response = $action($request);
	} else {
		$response = new HtmlResponse("Страница не найдена", 404);
	}
	
} catch(Exception $e) {
	$response = new HtmlResponse($e->getMessage(), 400);
}


$emitter = new SapiEmitter();
$emitter->emit($response);