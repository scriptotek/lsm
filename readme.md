## University of Oslo Library Services Middleware

Documentation at https://ub-lsm.uio.no/

Setup:

    composer install
    php artisan vendor:publish

Update OpenAPI documentation for Swagger UI:

    ./vendor/bin/openapi app -o public/swagger.json

Example nginx site config:

```
server {

    listen                443 ssl;
    listen                [::]:443 ssl;  # IPv6
    include ssl.conf;

    server_name           SOME.HOSTNAME;
    root                  /PATH/TO/PUBLIC/;
    error_log             /var/log/nginx/lsm.error.log;

    # = means exact location
    location = / {
        try_files /index.html =404;
    }

    # Handle php using php5-fpm
    location = /index.php {
        fastcgi_pass unix:/var/run/php5-fpm.sock;
        include fastcgi_params;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_intercept_errors on;
    }

    # Route everything else, except static files, to index.php
    location / {
        if (!-f $request_filename) {
            rewrite ^/.* /index.php;
        }
    }

}

server {
    listen       80;
    listen       [::]:80;  # IPv6
    server_name  SOME.HOSTNAME;
    rewrite      ^   https://SOME.HOSTNAME$request_uri? permanent;
}

```
