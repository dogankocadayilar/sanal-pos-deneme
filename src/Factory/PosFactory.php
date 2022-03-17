<?php


namespace App\Factory;


use App\Entity\Account\AbstractPosAccount;
use App\Exceptions\BankClassNullException;
use App\Exceptions\BankNotFoundException;
use App\Gateways\AbstractGateway;

class PosFactory
{
    /**
     * @param AbstractPosAccount $posAccount
     * @param array|string|null  $config config path or config array
     *
     * @return AbstractGateway
     *
     * @throws BankClassNullException
     * @throws BankNotFoundException
     */
    public static function createPosGateway(AbstractPosAccount $posAccount, $config = null)
    {
        if (is_string($config)) {
            $config = require $config;
        } elseif (empty($config)) {
            $config = require __DIR__.'/../../config/pos.php';
        }

        // Bank API Exist
        if (!array_key_exists($posAccount->getBank(), $config['banks'])) {
            throw new BankNotFoundException();
        }

        // Gateway Class
        $class = $config['banks'][$posAccount->getBank()]['class'];

        if (!$class) {
            throw new BankClassNullException();
        }

        $currencies = [];
        if (isset($config['currencies'])) {
            $currencies = $config['currencies'];
        }

        // Create Bank Class Object
        return new $class($config['banks'][$posAccount->getBank()], $posAccount, $currencies);
    }
}