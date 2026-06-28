<?php

Route::get('/test', function () {
    return 'Hello from GoMad!';
});

Route::get('/phpinfo', function () {
    phpinfo();
});
