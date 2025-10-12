<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Seeders Path
    |--------------------------------------------------------------------------
    |
    | Path of the folder with the seeders you want to versionate
    |
    */
    'path' => database_path('seeders'),

    /*
    |--------------------------------------------------------------------------
    | Table name
    |--------------------------------------------------------------------------
    |
    | As well as migrations it will be a table to manage the version of seeders, this
    | variable is the name of the table
    |
    */
    'table' => 'seeder_versions',
];
