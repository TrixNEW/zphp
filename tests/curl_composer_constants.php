<?php

foreach (['CURLM_OK', 'CURLM_BAD_HANDLE', 'CURLM_BAD_EASY_HANDLE', 'CURLM_OUT_OF_MEMORY', 'CURLM_INTERNAL_ERROR', 'CURLM_CALL_MULTI_PERFORM', 'CURLOPT_SSLKEYPASSWD'] as $name) {
    echo $name, '=', constant($name), "\n";
}
