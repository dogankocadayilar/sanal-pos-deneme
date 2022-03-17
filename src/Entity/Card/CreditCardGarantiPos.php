<?php

namespace App\Entity\Card;

/**
 * Class CreditCardGarantiPos
 */
class CreditCardGarantiPos extends AbstractCreditCard
{
    /**
     * @inheritDoc
     */
    public function getExpirationDate(): string
    {
        return $this->getExpireMonth().$this->getExpireYear();
    }
}