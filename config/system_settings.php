<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | Specifies the duration (in minutes) for which the settings should be
    | cached. This improves performance by reducing database queries.
    |
    */
    'cache_duration' => 60,

    /*
    |--------------------------------------------------------------------------
    | Allowed Types
    |--------------------------------------------------------------------------
    |
    | Defines the types of data that are allowed for settings. This ensures
    | that only valid data types are stored and retrieved, preventing errors.
    | You can customize this list to include any other data types you need.
    |
    */
    'allowed_types' => ['string', 'integer', 'boolean', 'json'],

    /*
    |--------------------------------------------------------------------------
    | System Settings Model
    |--------------------------------------------------------------------------
    |
    | Here you may specify the model class that should be used for the system
    | settings. This allows you to extend or replace the default model.
    | Customizing this model enables you to add your own methods, relationships,
    | or other logic.
    |
    */
    'model' => \Venom\SystemSettings\Models\SystemSettings::class,

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | Defines a prefix for the cache keys used when storing system settings.
    | This can be useful to avoid cache key conflicts in a multi-tenant or
    | complex application.
    |
    */
    'cache_key_prefix' => 'system_settings',

    /*
    |--------------------------------------------------------------------------
    | Default Type
    |--------------------------------------------------------------------------
    |
    | Specifies the default type to be used when a type is not provided during
    | the setting creation or update process. This ensures consistency and
    | reduces the risk of errors.
    |
    */
    'default_type' => 'string',
];