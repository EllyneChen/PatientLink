<?php

return [

    /*
    
     Application Name
    
    
     This value is the name of your application, which will be used when the
     framework needs to place the application's name in a notification or
     other UI elements where an application name needs to be displayed.
    
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    
     Application Environment
    
    
     This value determines the "environment" your application is currently
     running in, and may determine preference of configuration to various
     services the application utilizes. 
    
    */

    'env' => env('APP_ENV', 'production'),

    /*
    
     Application Debug Mode
    
    
     When the application is in debug mode, detailed error messages with
     stack traces will be shown on every error that occurs within the
     application. If disabled, a simple generic error page is shown.
    
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    
     Application URL
    
    
     This URL is used by the console to properly generate URLs when using
     the Artisan command line tool.It`s also added to the root of
     the application so that it's available within Artisan commands.

    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    
     Application Timezone
     Timezone set to UTC.
    */

    'timezone' => 'UTC',

    /*
    
     Application Locale Configuration
    
    
     The application locale determines the default locale that will be used
     by Laravel's translation.
    
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    
     Encryption Key
    
    
     This key is utilized by Laravel's encryption services and should be set
     to a random, 32 character string to ensure that all encrypted values
     are secure.Done prior to deployment of the application.
    
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    
     Maintenance Mode Driver
    
     These configuration options determine the driver used to determine and
     manage Laravel's "maintenance mode" status. The "cache" driver will
     allow maintenance mode to be controlled across multiple machines.
    
     Supported drivers: "file", "cache"
    
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
