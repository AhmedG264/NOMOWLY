<?php

namespace App\Exceptions;

use Exception;

class UnbalancedLedgerTransactionException extends Exception
{
    public function __construct(string $message = "القيود المحاسبية غير متوازنة (مجموع المدين لا يساوي مجموع الدائن).", int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
