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

    /*
    |--------------------------------------------------------------------------
    | History table name
    |--------------------------------------------------------------------------
    |
    | Every time a seeder is registered (run or hash-only), a row is added to this
    | table with the new hash, the previous one and when it happened
    |
    */
    'history_table' => 'seeder_version_history',

    /*
    |--------------------------------------------------------------------------
    | Execution order
    |--------------------------------------------------------------------------
    |
    | Seeders listed here run first, in this order. Seeders not listed run
    | after them, in alphabetical order. Example: [RoleSeeder::class, UserSeeder::class]
    |
    */
    'order' => [],

    /*
    |--------------------------------------------------------------------------
    | Excluded seeders
    |--------------------------------------------------------------------------
    |
    | Seeders that will never be versioned or run. DatabaseSeeder is excluded by
    | default because it calls the other seeders, which would make them run twice.
    | Use the class name, with or without namespace.
    |
    */
    'except' => [
        'DatabaseSeeder',
    ],
];
