<?php

/*
|--------------------------------------------------------------------------
| Custom application config
|--------------------------------------------------------------------------
|
| Mirrors the app-specific env() keys that were previously read directly
| from env() inside controllers/services. Reading env() outside of config
| files breaks `php artisan config:cache` (env() returns null once cached).
| All application code should read these via config('custom.<key>') so the
| config cache can be safely enabled in production.
|
*/

return [
    'default_language'                  => env('DEFAULT_LANGUAGE', 'en'),
    'demo_mode'                         => env('DEMO_MODE'),
    'app_name'                          => env('APP_NAME'),
    'app_version'                       => env('APP_VERSION'),
    'app_debug'                         => env('APP_DEBUG'),
    'app_url'                           => env('APP_URL', 'http://localhost'),

    // Front-end public keys / analytics (safe to render client-side)
    'tracking_id'                       => env('TRACKING_ID'),
    'enable_google_analytics'           => env('ENABLE_GOOGLE_ANALYTICS'),
    'enable_google_adsense'             => env('ENABLE_GOOGLE_ADSENSE'),
    'facebook_pixel_id'                 => env('FACEBOOK_PIXEL_ID'),
    'recaptchav3_sitekey'               => env('RECAPTCHAV3_SITEKEY'),
    'stripe_key'                        => env('STRIPE_KEY'),
    'midtrans_client_key'               => env('MIDTRANS_CLIENT_KEY'),

    'default_currency'                  => env('DEFAULT_CURRENCY'),
    'default_currency_symbol'           => env('DEFAULT_CURRENCY_SYMBOL'),
    'default_currency_symbol_alignment' => env('DEFAULT_CURRENCY_SYMBOL_ALIGNMENT'),
    'default_currency_rate'             => env('DEFAULT_CURRENCY_RATE'),
    'default_pagination'                => env('DEFAULT_PAGINATION'),

    'mail_from_address'                 => env('MAIL_FROM_ADDRESS'),
    'mail_from_name'                    => env('MAIL_FROM_NAME'),
    'mail_username'                     => env('MAIL_USERNAME'),
    'mail_mailer'                       => env('MAIL_MAILER'),

    'db_host'                           => env('DB_HOST'),
    'db_database'                       => env('DB_DATABASE'),
    'db_username'                       => env('DB_USERNAME'),
    'db_password'                       => env('DB_PASSWORD'),

    'invoice_lang'                      => env('INVOICE_LANG'),
    'invoice_font'                      => env('INVOICE_FONT'),

    // AI providers
    'ai_provider'                       => env('AI_PROVIDER', 'gemini'),
    'openai_api_key'                    => env('OPENAI_API_KEY'),
    'openai_secret_key'                 => env('OPENAI_SECRET_KEY'),
    'openai_model'                      => env('OPENAI_MODEL', 'gpt-4-turbo'),
    'openai_max_tokens'                 => env('OPENAI_MAX_TOKENS', 4096),
    'gemini_api_key'                    => env('GEMINI_API_KEY'),
    'vertex_models'                     => env('VERTEX_MODELS', 'gemini-2.5-flash,gemini-3.1-pro-preview'),
    'vertex_location'                   => env('VERTEX_LOCATION', 'global'),
    'google_cloud_project'              => env('GOOGLE_CLOUD_PROJECT'),
    'google_cloud_bucket'               => env('GOOGLE_CLOUD_BUCKET'),

    // Payment gateways
    'stripe_secret'                     => env('STRIPE_SECRET'),
    'midtrans_server_key'               => env('MIDTRANS_SERVER_KEY'),
    'paypal_notify_url'                 => env('PAYPAL_NOTIFY_URL', ''),
    'paystack_currency_code'            => env('PAYSTACK_CURRENCY_CODE', 'USD'),
    'razorpay_key'                      => env('RAZORPAY_KEY'),
    'razorpay_secret'                   => env('RAZORPAY_SECRET'),
    'iyzico_api_key'                    => env('IYZICO_API_KEY'),
    'iyzico_secret_key'                 => env('IYZICO_SECRET_KEY'),
    'molile_api_key'                    => env('MOLILE_API_KEY'),
    'mercadopago_secret_key'            => env('MERCADOPAGO_SECRET_KEY'),
    'yookassa_shop_id'                  => env('YOOKASSA_SHOP_ID'),
    'yookassa_secret_key'               => env('YOOKASSA_SECRET_KEY'),
    'yookassa_currency_code'            => env('YOOKASSA_CURRENCY_CODE'),
    'yookassa_reciept'                  => env('YOOKASSA_RECIEPT'),
    'yookassa_vat'                      => env('YOOKASSA_VAT'),

    // Twilio
    'twilio_sid'                        => env('TWILIO_SID'),
    'twilio_auth_token'                 => env('TWILIO_AUTH_TOKEN'),
    'valid_twilio_number'               => env('VALID_TWILIO_NUMBER'),
];
