<?php

return [
    'assest_attchment'       => [
        'default_file_path'     => 'mobile-app/temp/',
        'claim_file_path'       => 'mobile-app/claims/',
        'max_upload_size_limit' => 2000000,
        'allowed_filetypes'     => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    ],

    'thumbnail_url'          => env('APP_FILE_URL'),
    'reset_url'              => env('APP_RESET_URL'),
    's3_bucket_url'          => env('S3_UPLOADS_BUCKET_URL'),

    'workspace_document'     => [
        'workspace_file_path'   => 'public/workspace/',
        'max_upload_size_limit' => 2000000,
        'allowed_filetypes'     => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    ],

    'portal_document'        => [
        'portal_file_path'      => 'public/portal/',
        'max_upload_size_limit' => 2000000,
        'allowed_filetypes'     => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    ],
   
    'asset_document' => [
        'asset_file_path' => 'public/assets/',
    ],

    'tile_document'          => [
        'tile_file_path'        => 'public/tiles/',
        'max_upload_size_limit' => 2000000,
        'allowed_filetypes'     => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    ],

    'external_link_document' => [
        'external_link_file_path' => 'public/external_link/',
        'max_upload_size_limit'   => 2000000,
        'allowed_filetypes'       => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    ],
    'redirect_email' => env('REDIRECT_EMAIL'),
    'superadmin_email' => env('SUPERADMIN_EMAIL'),
];
