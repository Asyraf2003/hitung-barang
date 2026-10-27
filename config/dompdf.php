<?php

return [
    'public_path' => is_dir(base_path('public'))
        ? base_path('public')
        : base_path('../public_html'),
];
