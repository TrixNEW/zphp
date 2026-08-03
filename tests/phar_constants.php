<?php

foreach (['BZ2', 'GZ', 'NONE', 'PHAR', 'TAR', 'ZIP', 'MD5', 'SHA1', 'SHA256', 'SHA512', 'OPENSSL', 'OPENSSL_SHA256', 'OPENSSL_SHA512'] as $name) {
    echo $name, '=', constant('Phar::' . $name), "\n";
}
