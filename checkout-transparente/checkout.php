<?php

namespace App\Http\Services;

header('Content-Type: text/html; charset=utf-8');

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPSC\PagSeguro\Credentials;
use PHPSC\PagSeguro\Environments\Production;
use PHPSC\PagSeguro\Environments\Sandbox;
use PHPSC\PagSeguro\Customer\Customer;
use PHPSC\PagSeguro\Items\Item;
use PHPSC\PagSeguro\Requests\Checkout\CheckoutService;
use PagSeguro\Library;
use PagSeguro\Services;
use Doctrine\Common\Annotations\AnnotationRegistry;

class PagamentoService {

    private static function init(){
        Library::initialize();
        Library::cmsVersion()->setName("")->setRelease("");
        Library::moduleVersion()->setName("")->setRelease("");
        \PagSeguro\Configuration\Configure::setEnvironment("production" || "sandbox");
        \PagSeguro\Configuration\Configure::setAccountCredentials("email", "token");
        \PagSeguro\Configuration\Configure::setCharset('UTF-8');
    }
    
    public static function getSession() {
        self::init();
        $sessionCode = Services\Session::create(\PagSeguro\Configuration\Configure::getAccountCredentials());
        return response()->json($sessionCode->getResult());
    }

    public static function paymentWithCreditCard($request,$parametros) {
        self::init();
        try {
            $result = self::buildPayment($request,$parametros)->register(\PagSeguro\Configuration\Configure::getAccountCredentials());      
            return response()->json(['code'=>$result->getCode()],200);
        } catch (\Exception $e) {
            return response()->json([$e->getMessage()],500);
        }
    }
    
    private static function buildPayment($parametros){
        $build = new \PagSeguro\Domains\Requests\DirectPayment\CreditCard();

        $build->setMode('DEFAULT');
        $build->setCurrency($parametros->currency);
        $build->setSender()
                ->setName($parametros->display_name)
                ->setEmail($parametros->email)
                ->setPhone()->withParameters($parametros->ddd, $parametros->phone);

        $build->addItems()->withParameters(1, 'Descrição do Item', 1, 1.00);
        $build->setSender()->setDocument()->withParameters('CPF', $parametros->cpf);
        $build->setSender()->setHash($parametros->hash);
        $build->setInstallment()->withParameters(1, '1.00');

        $build->setShipping()
                ->setAddress()->withParameters(
                $parametros->rua,
                $parametros->numero,
                $parametros->bairro,
                $parametros->cep,
                $parametros->cidade, 
                $parametros->estado,
                $parametros->pais,
                $parametros->complemento
        );
        $build->setBilling()
                ->setAddress()->withParameters(
                $parametros->numero,
                $parametros->bairro,
                $parametros->cep,
                $parametros->cidade, 
                $parametros->estado,
                $parametros->pais,
                $parametros->complemento
        );

        $build->setToken($request->token);
        $build->setHolder()->setName($parametros->nomeCLiente);

        $build->setHolder()->setBirthDate($parametros->aniversario);
        $build->setHolder()->setPhone()->withParameters($parametros->ddd, $parametros->phone));
        $build->setHolder()->setDocument()->withParameters('CPF', $parametros->cpf);
        return $build;
    }

}
