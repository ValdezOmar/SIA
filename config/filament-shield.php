<?php

return [
    'shield_resource' => [
        'should_register_navigation' => true,
        'slug' => 'shield/roles',
        'navigation_sort' => -1,
        'navigation_badge' => true,
        'navigation_group' => true,
        'is_globally_searchable' => false,
        'show_model_path' => false,
        'is_scoped_to_tenant' => true,
        'cluster' => null,
        'tabs' => ['pages' => false, 'widgets' => true, 'resources' => true, 'custom_permissions' => true],
    ],

    'tenant_model' => null,

    'auth_provider_model' => App\Models\User::class,

    // The callback in LegacyShieldPermissions preserves the database's v3 keys.
    'permissions' => ['separator' => ':', 'case' => 'pascal', 'generate' => true, 'format_custom_permission_keys' => false],
    'policies' => [
        'path' => app_path('Policies'), 'merge' => false, 'generate' => true,
        'methods' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'single_parameter_methods' => ['viewAny', 'create', 'deleteAny', 'forceDeleteAny', 'restoreAny', 'reorder'],
    ],
    'localization' => ['enabled' => false, 'key' => 'filament-shield::filament-shield.resource_permission_prefixes_labels'],
    'resources' => [
        'subject' => 'class',
        'manage' => [
            App\Filament\Resources\Configuracion\RoleResource::class => ['viewAny', 'view', 'create', 'update', 'delete', 'deleteAny'],
            App\Filament\Resources\Almacen\InventarioResource::class => ['viewAny', 'update', 'programarInventario'],
            App\Filament\Resources\RRHH\EmpleadoResource::class => ['viewAny', 'create', 'update', 'verEmpleadosSucursal', 'verEmpleadosTodos'],
            App\Filament\Resources\RRHH\AsistenciaResource::class => ['viewAny', 'create', 'verMarcacionPropia', 'verMarcacionSucursal', 'verMarcacionTodos', 'exportarPdf'],
            App\Filament\Resources\RRHH\PerfilEmpleadoResource::class => ['viewAny', 'update'],
            App\Filament\Resources\RRHH\DirectorioResource::class => ['viewAny'],
            App\Filament\Clusters\Sistema\Resources\ParametroResource::class => ['viewAny', 'view', 'update'],
        ],
        'exclude' => [],
    ],
    'pages' => ['subject' => 'class', 'prefix' => 'page', 'exclude' => [Filament\Pages\Dashboard::class]],
    'widgets' => ['subject' => 'class', 'prefix' => 'widget', 'exclude' => [Filament\Widgets\AccountWidget::class, Filament\Widgets\FilamentInfoWidget::class]],
    'custom_permissions' => [],

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => true,
        'intercept_gate' => 'before', // after
    ],

    'panel_user' => [
        'enabled' => true,
        'name' => 'panel_user',
    ],

    'permission_prefixes' => [
        'resource' => [
            'view_any',
            'view',            
            'create',
            'update',
            // 'restore',
            // 'restore_any',
            // 'replicate',
            // 'reorder',
            'delete',
            //'delete_any',
            // 'force_delete',
            // 'force_delete_any',
        ],

        'page' => 'page',
        'widget' => 'widget',
    ],

    'entities' => [
        'pages' => false,
        'widgets' => true,
        'resources' => true,
        'custom_permissions' => true,
    ],

    'generator' => [
        'option' => 'policies_and_permissions',
        'policy_directory' => 'Policies',
        'policy_namespace' => 'Policies',
    ],

    'exclude' => [
        'enabled' => true,

        'pages' => [
            'Dashboard',
        ],

        'widgets' => [
            'AccountWidget',
            'FilamentInfoWidget',            
        ],

        'resources' => [],
    ],

    'discovery' => [
        // Incluye automáticamente los resources registrados en los paneles, incluso los agregados a futuro.
        'discover_all_resources' => true,
        'discover_all_widgets' => true,
        'discover_all_pages' => false,
    ],

    'register_role_policy' => true,
];
