<?php

namespace App\Exceptions;

use Exception;

class InsufficientFundsException extends Exception
{
    public function __construct(string $message = "الرصيد المتاح في المحفظة غير كافٍ لإتمام العملية المالية.", int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
