<?php

namespace App\Entity\Card;

/**
 * Class CreditCardPayFor
 */
class CreditCardPayFor extends AbstractCreditCard
{
    /**
     * @inheritDoc
     */
    public function getExpirationDate(): string
    {
        return $this->getExpireMonth().$this->getExpireYear();
    }
}