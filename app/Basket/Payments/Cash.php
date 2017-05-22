<?php

namespace App\Basket\Payments;

use App\Basket\Models\Payment;

class Cash extends Payment
{
    /**
     * Computes the total payment amount.
     *
     * @return float
     */
    public function computeAmount()
    {
        return $this->payment->amount;
    }
}
