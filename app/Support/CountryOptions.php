<?php

namespace App\Support;

class CountryOptions
{
    public static function all(): array
    {
        $bundle = \ResourceBundle::create('en', 'ICUDATA-region');
        $countries = [];

        if (! $bundle) {
            return $countries;
        }

        $countryBundle = $bundle->get('Countries');

        foreach ($countryBundle as $countryCode => $countryName) {
            if (preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
                continue;
            }

            $countries[$countryCode] = (string) $countryName;
        }

        asort($countries);

        return $countries;
    }
}
