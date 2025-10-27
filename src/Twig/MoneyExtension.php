<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MoneyExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('money', [$this, 'formatMoney']),
        ];
    }

    /**
     * Formatuje kwotę wyrażoną w złotówkach (zł) do postaci "12,34 zł".
     * Akceptuje stringi z przecinkiem lub kropką, liczby typu float lub int.
     *
     * @param float|int|string|null $value
     * @return string
     */
    public function formatMoney($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Jeśli otrzymamy string z przecinkiem zastąpimy przecinek kropką
        if (is_string($value)) {
            $v = str_replace(',', '.', $value);
            if (is_numeric($v)) {
                $value = $v + 0;
            }
        }

        // Traktujemy obecną wartość jako złote
        $zloty = (float)$value;

        // Formatowanie: 2 miejsca po przecinku, przecinek jako separator dziesiętny, spacja jako separator tysiąca
        return number_format($zloty, 2, ',', ' ') . ' zł';
    }
}
