<?php

namespace App\Support;

final class Dinheiro
{
    public static function brl(float|int $reais): string
    {
        return 'R$ '.number_format((float) $reais, 2, ',', '.');
    }
}
