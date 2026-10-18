<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case FAKE = 'fake';
    case MOYASAR = 'moyasar';
    case TAP = 'tap';

    public function label(): string
    {
        return match ($this) {
            self::FAKE => 'بوابة الاختبار التجريبية (Sandbox)',
            self::MOYASAR => 'ميسر (Moyasar - مدى / فيزا)',
            self::TAP => 'تاب للمدفوعات (Tap Payments)',
        };
    }
}
