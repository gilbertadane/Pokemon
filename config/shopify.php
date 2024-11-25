<?php

return [

    // Shopify Config
    'store_name' => getenv('SHOPIFY_STORE_NAME', '18d497-2.myshopify.com'),
    'api_key' => getenv('SHOPIFY_API_KEY', '5e3abc16e3165650e6e319e823485b7a'),
    'token' => getenv('SHOPIFY_API_TOKEN', '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'),
    'version' => getenv('SHOPIFY_API_VERSION', '2024-04'),
    'scopes' => getenv('SHOPIFY_API_SCOPES', 'read_products,write_products'),

    'product_status' => [
        'draft' => 'draft',
        'active' => 'active',
        'archived' => 'archived'
    ],

    'currency_converter_api_key' => getenv('CURRENCY_CONVERTER_API_KEY', '179a6b0b94a40c09eecc99a28c700eea'),
    'currency_converter_base_currency' => getenv('CURRENCY_CONVERTER_BASE_CURRENCY', 'USD'),
    'currency_converter_currencies' => getenv('CURRENCY_CONVERTER_CURRENCIES', 'AUD'),
];
?>