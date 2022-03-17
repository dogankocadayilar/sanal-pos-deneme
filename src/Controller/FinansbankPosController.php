<?php

namespace App\Controller;

use App\Entity\Card\CreditCardGarantiPos;
use App\Entity\Card\CreditCardPayFor;
use App\Exceptions\BankClassNullException;
use App\Exceptions\BankNotFoundException;
use App\Factory\AccountFactory;
use App\Factory\PosFactory;
use App\Gateways\AbstractGateway;
use App\Gateways\PayForPos;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class FinansbankPosController extends AbstractController
{   
    private $request;
    private $pos;
    private $baseUrl;
    private $ip;

    public function __construct()
    {
        $hostUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')."://$_SERVER[HTTP_HOST]";

        $path = '/finansbank';
        $this->baseUrl = $hostUrl.$path;

        $this->request = Request::createFromGlobals();
        $this->ip = $this->request->getClientIp();
        $account = AccountFactory::createPayForAccount('qnbfinansbank-payfor', '085300000009704', 'QNB_API_KULLANICI_3DPAY', 'UcBN0', '3d_pay', '12345678');

        try {
            $this->pos = PosFactory::createPosGateway($account);
            $this->pos->setTestMode(true);
        } catch (BankNotFoundException $e) {
            dump($e->getCode(), $e->getMessage());
        } catch (BankClassNullException $e) {
            dump($e->getCode(), $e->getMessage());
        }

    }

    #[Route('/finansbank/index', name: 'app_finansbank_pos')]
    public function form(): Response
    {

        $templateTitle = '3D Pay Model Payment';

        if ($this->request->getMethod() !== 'POST') {
            echo new RedirectResponse($this->baseUrl);
            exit();
        }

        $orderId = date('Ymd').strtoupper(substr(uniqid(sha1(time())), 0, 4));

        $amount = (float) 10.55;
        $installment = '0';

        $successUrl = $this->baseUrl.'/response';
        $failUrl = $this->baseUrl.'/response';

        $rand = microtime();

        $order = [
            'id'          => $orderId,
            'email'       => 'mail@customer.com', // optional
            'name'        => 'John Doe', // optional
            'amount'      => $amount,
            'installment' => $installment,
            'currency'    => 'TRY',
            'ip'          => $this->ip,
            'success_url' => $successUrl,
            'fail_url'    => $failUrl,
            'lang'        => PayForPos::LANG_TR,
            'rand'        => $rand,
        ];

        $card = new CreditCardPayFor(
            $this->request->get('number'),
            $this->request->get('year'),
            $this->request->get('month'),
            $this->request->get('cvv'),
            $this->request->get('name'),
            $this->request->get('type')
        );

        $this->pos->prepare($order, AbstractGateway::TX_PAY, $card);

        $formData = $this->pos->get3DFormData();
                    
        return $this->render('finansbank_pos/form.html.twig', [
            'title' => $templateTitle,
            'formdata' => $formData,
        ]);
    }

    #[Route('/finansbank', name: 'app_finansbank_index')]
    public function index() :Response
    {
        return $this->render('finansbank_pos/index.html.twig');
    }

    #[Route('/finansbank/response', name: 'app_finansbank_response')]
    public function rp() :Response
    {

        if ($this->request->getMethod() !== 'POST') {
            echo new RedirectResponse($this->baseUrl);
            exit();
        }
        $orderId = date('Ymd').strtoupper(substr(uniqid(sha1(time())), 0, 4));

        $amount = (float) 10.55;
        $installment = '0';

        $successUrl = $this->baseUrl.'/response';
        $failUrl = $this->baseUrl.'/response';

        $rand = microtime();

        $order = [
            'id'          => $orderId,
            'email'       => 'mail@customer.com', // optional
            'name'        => 'John Doe', // optional
            'amount'      => $amount,
            'installment' => $installment,
            'currency'    => 'TRY',
            'ip'          => $this->ip,
            'success_url' => $successUrl,
            'fail_url'    => $failUrl,
            'lang'        => PayForPos::LANG_TR,
            'rand'        => $rand,
        ];


        $this->pos->prepare($order, AbstractGateway::TX_PAY);
        $this->pos->payment();

        $response = $this->pos->getResponse();

        return $this->render('finansbank_pos/response.html.twig', [
            'response' => $response,
            'pos' => $this->pos,
            'POST' => $_POST
        ]);
    }
}
