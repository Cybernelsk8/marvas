<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Datos de contacto público
    |--------------------------------------------------------------------------
    |
    | Se usan en la landing page. Las URLs son públicas; no guardar aquí
    | credenciales. El número de WhatsApp debe incluir el código de país y
    | solo dígitos, por ejemplo: 50258623352.
    |
    */

    'country_code' => env('MARVAS_COUNTRY_CODE', '502'),

    'social' => [
        'instagram' => env('MARVAS_INSTAGRAM_URL'),
        'facebook' => env('MARVAS_FACEBOOK_URL'),
        'whatsapp' => env('MARVAS_WHATSAPP_NUMBER'),
    ],

    'whatsapp_message' => env(
        'MARVAS_WHATSAPP_MESSAGE',
        'Hola, quisiera información sobre una cita en MARVAS.',
    ),

];
