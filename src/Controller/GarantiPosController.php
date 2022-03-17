<?php

namespace App\Controller;

use App\Entity\Account\AbstractPosAccount;
use App\Entity\Card\CreditCardGarantiPos;
use App\Exceptions\BankClassNullException;
use App\Exceptions\BankNotFoundException;
use App\Factory\AccountFactory;
use App\Factory\PosFactory;
use App\Gateways\AbstractGateway;
use App\Gateways\GarantiPos;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;


class GarantiPosController extends AbstractController
{   
    private $pos;
    private $baseUrl;
    private $request;

    public function __construct()
    {
        $hostUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')."://$_SERVER[HTTP_HOST]";

        $path = '/garanti';
        $this->baseUrl = $hostUrl.$path;
        $this->request = Request::createFromGlobals();
        $account = AccountFactory::createGarantiPosAccount('garanti', '7000679', 'PROVAUT', '123qweASD/', '30691298', '3d', '12345678');

        try {
            $this->pos = PosFactory::createPosGateway($account);
            $this->pos->setTestMode(true);
        } catch (BankNotFoundException $e) {
            dump($e->getCode(), $e->getMessage());
        } catch (BankClassNullException $e) {
            dump($e->getCode(), $e->getMessage());
        }
    }

    #[Route('/garanti/index', name: 'app_garanti_index', methods:['POST'])]
    public function form(): Response
    {   

        $ip = $this->request->getClientIp();

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
            'ip'          => $ip,
            'success_url' => $successUrl,
            'fail_url'    => $failUrl,
            'lang'        => GarantiPos::LANG_TR,
            'rand'        => $rand,
        ];
        


        $card = new CreditCardGarantiPos(
            $this->request->get('number'),
            $this->request->get('year'),
            $this->request->get('month'),
            $this->request->get('cvv'),
            $this->request->get('name'),
            $this->request->get('type')
        );

        $this->pos->prepare($order, AbstractGateway::TX_PAY, $card);

        dump($this->pos);
        
        $formData = $this->pos->get3DFormData();
        dump($formData);
                    
        return $this->render('garanti_pos/form.html.twig', [
            'title' => $templateTitle,
            'formdata' => $formData,
        ]);
    }

    #[Route('/garanti', name: 'app_garanti_pos')]
    public function index() :Response
    {
        return $this->render('garanti_pos/index.html.twig');
    }

    #[Route('/garanti/response', name: 'app_garanti_response', methods:['POST'])]
    public function rp(Request $request) :Response
    {
        // if ($request->getMethod() !== 'POST') {
        //     echo new RedirectResponse($baseUrl);
        //     exit();
        // }
        $ip = $this->request->getClientIp();
        
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
            'ip'          => $ip,
            'success_url' => $successUrl,
            'fail_url'    => $failUrl,
            'lang'        => GarantiPos::LANG_TR,
            'rand'        => $rand,
        ];


        $this->pos->prepare($order, AbstractGateway::TX_PAY);
        $this->pos->payment();
        $response = $this->pos->getResponse();

        return $this->render('garanti_pos/response.html.twig', [
            'response' => $response,
            'pos' => $this->pos,
            'POST' => $_POST
        ]);
    }
}
