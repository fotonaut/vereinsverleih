<?php

// Angaben des Betreibers dieser Instanz (Impressum / Datenschutz). Über die .env setzen.
return [
    'brand' => env('IMPRINT_BRAND', 'Betreiber dieser Instanz'),
    'name' => env('IMPRINT_NAME', ''),
    'street' => env('IMPRINT_STREET', ''),
    'city' => env('IMPRINT_CITY', ''),
    'country' => env('IMPRINT_COUNTRY', 'Deutschland'),
    'email' => env('IMPRINT_EMAIL', ''),
    'phone' => env('IMPRINT_PHONE', ''),
    'hoster' => env('IMPRINT_HOSTER', 'manitu GmbH, Welvertstraße 2, 66606 St. Wendel'),
    // Nach wie vielen Tagen unbestätigte Gast-Anfragen automatisch gelöscht werden
    'unverified_retention_days' => 7,
];
