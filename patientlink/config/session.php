<?php

use Illuminate\Support\Str;

return [

    /*
    
     Default Session Driver
     This option determines the default session driver that is utilized for
     incoming requests. Laravel supports a variety of storage options to
     persist session data. Database storage is a great default choice.
    
     Supported: "file", "cookie", "database", "memcached",
                "redis", "dynamodb", "array"
    
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    
     Session Lifetime
     The number of minutes that the session should be allowed to remain idle before it expires.
     If needed to expire immediately when the browser is closed then it may be
     indicated via the expire_on_close configuration option.
    
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
     Session Encryption
     This option allows one to easily specify that all of the session data
     should be encrypted before it's stored. All encryption is performed
     automatically by Laravel and you may use the session like normal.
    
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    
     Session File Location
     When utilizing the "file" session driver, the session files are placed
     on disk. The default storage location is defined here; however, you
     are free to provide another location where they should be stored.
    
    */

    'files' => storage_path('framework/sessions'),

    /*
    
     Session Database Connection
     When using the "database" or "redis" session drivers, 
     connection that should be used to manage these sessions may be specified.
    This should correspond to a connection in your database configuration options.
    
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
     Session Database Table
     When using the "database" session driver, the table to
     be used to store sessions may be specified. 
    
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
     Session Cache Store
     When using one of the framework's cache driven session backends,
     define the cache store which should be used to store the session data
     between requests may be specified. This must match one of your defined cache stores.
    
     Affects: "dynamodb", "memcached", "redis"
    
    */

    'store' => env('SESSION_STORE'),

    /*
     Session Sweeping Lottery
     Some session drivers must manually sweep their storage location to get
     rid of old sessions from storage. Here are the chances that it will
     happen on a given request. By default, the odds are 2 out of 100.
    
    */

    'lottery' => [2, 100],

    /*
     Session Cookie Name
     Here, the name of the session cookie that is created by
     the framework may be specified.
    Typically, you should not need to change this value
     since doing so does not grant a meaningful security improvement.
    
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session'
    ),

    /*
    
     Session Cookie Path
     The session cookie path determines the path for which the cookie will
     be regarded as available. Typically, this will be the root path of
     your application, with freedom to change this when necessary.
    
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    
     Session Cookie Domain
     This value determines the domain and subdomains the session cookie is
     available to. By default, the cookie will be available to the root
     domain without subdomains. Typically, this shouldn't be changed.
    
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    
     HTTPS Only Cookies
     By setting this option to true, session cookies will only be sent back
     to the server if the browser has a HTTPS connection. This will keep
     the cookie from being sent to you when it can't be done securely.
    
    */

    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    
     HTTP Access Only
    
     Setting this value to true will prevent JavaScript from accessing the
     value of the cookie and the cookie will only be accessible through
     the HTTP protocol. It's unlikely you should disable this option.
    
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    
     Same-Site Cookies
    
    
     This option determines how your cookies behave when cross-site requests
     take place, and can be used to mitigate CSRF attacks. By default, we
     will set this value to "lax" to permit secure cross-site requests.
    
     See: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value
    
     Supported: "lax", "strict", "none", null
    
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    
     Partitioned Cookies
     Setting this value to true will tie the cookie to the top-level site for
     a cross-site context. Partitioned cookies are accepted by the browser
     when flagged "secure" and the Same-Site attribute is set to "none".
    
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

    /*
    Session Serialization
    
    
     This value controls the serialization strategy for session data, which
     is JSON by default. Setting this to "php" allows the storage of PHP
     objects in the session but can make an application vulnerable to
     "gadget chain" serialization attacks if the APP_KEY is leaked.
    
     Supported: "json", "php"
    
    */

    'serialization' => 'json',

];
