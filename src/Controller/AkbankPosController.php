<?php

namespace App\Controller;

use App\Entity\Card\CreditCardEstPos;
use App\Exceptions\BankClassNullException;
use App\Exceptions\BankNotFoundException;
use App\Factory\AccountFactory;
use App\Factory\PosFactory;
use App\Gateways\AbstractGateway;
use App\Gateways\EstPos;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AkbankPosController extends AbstractController
{

    private string $baseUrl;
    private $request;
    private $pos;
    private $ip;



    public function __construct()
    {
        $hostUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')."://$_SERVER[HTTP_HOST]";

        $path = '/akbank';
        $this->baseUrl = $hostUrl.$path;

        $this->request = Request::createFromGlobals();
        $this->ip = $this->request->getClientIp();
        $account = AccountFactory::createEstPosAccount('akbank', '100200000', 'AKBANK', 'AKBANK01', '3d_pay', '123456');

        try {
            $this->pos = PosFactory::createPosGateway($account);
            $this->pos->setTestMode(true);
        } catch (BankNotFoundException $e) {
            dump($e->getCode(), $e->getMessage());
        } catch (BankClassNullException $e) {
            dump($e->getCode(), $e->getMessage());
        }
    }

    #[Route('/akbank/index', name: 'app_akbank_index')]
    public function form(Request $request): Response
    {
        $templateTitle = '3D Pay Model Payment';

        if ($this->request->getMethod() !== 'POST') {
            echo new RedirectResponse($this->baseUrl);
            exit();
        }

        $orderId = date('Ymd').strtoupper(substr(uniqid(sha1(time())), 0, 4));

        $amount = (float) 1;
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
            'lang'        => EstPos::LANG_TR,
            'rand'        => $rand,
        ];


        $file = fopen('C:\xampp\htdocs\Symfony-Projects\sanal-pos-garanti\\n'.$order["id"], "a+");
        fwrite($file, json_encode($order, JSON_UNESCAPED_UNICODE), strlen(json_encode($order, JSON_UNESCAPED_UNICODE)));
        fclose($file);



        $year = substr($this->request->get("year"), 2, strlen($this->request->get("year")));



        $card = new CreditCardEstPos(
            $this->request->get('number'),
            $year,
            $this->request->get('month'),
            $this->request->get('cvv'),
            $this->request->get('name'),
            $this->request->get('type')
        );

        $this->pos->prepare($order, AbstractGateway::TX_PAY, $card);

        $formData = $this->pos->get3DFormData();
                    
        return $this->render('akbank_pos/form.html.twig', [
            'title' => $templateTitle,
            'formdata' => $formData,
        ]);
    }

    #[Route('/akbank', name: 'app_akbank_pos')]
    public function index() :Response
    {



        return $this->render('akbank_pos/index.html.twig');
    }

    #[Route('/akbank/response', name: 'app_akbank_response')]
    public function rp() :Response
    {

        if ($this->request->getMethod() !== 'POST') {
            echo new RedirectResponse($this->baseUrl);
            exit();
        }

        
        $orderId = date('Ymd').strtoupper(substr(uniqid(sha1(time())), 0, 4));

        $amount = (float) 1;
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
            'lang'        => EstPos::LANG_TR,
            'rand'        => $rand,
        ];

        $this->pos->prepare($order, AbstractGateway::TX_PAY);
        $this->pos->payment();
        $response = $this->pos->getResponse();

        return $this->render('akbank_pos/response.html.twig', [
            'response' => $response,
            'pos' => $this->pos,
            'POST' => $_POST
        ]);
    }
}
