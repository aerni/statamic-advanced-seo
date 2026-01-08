<?php

use Aerni\AdvancedSeo\UpdateScripts\MigrateUserPermissions;
use Statamic\Facades\Role;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

function runPermissionsMigrationScript(): void
{
    (new MigrateUserPermissions('aerni/advanced-seo'))->update();
}

it('migrates site-level permissions to configure seo', function () {
    $role = Role::make('site-admin')
        ->addPermission('edit seo general defaults')
        ->addPermission('edit seo indexing defaults')
        ->save();

    runPermissionsMigrationScript();

    $role = Role::find('site-admin');

    expect($role->hasPermission('configure seo'))->toBeTrue();
    expect($role->hasPermission('edit seo general defaults'))->toBeFalse();
    expect($role->hasPermission('edit seo indexing defaults'))->toBeFalse();
});

it('migrates content-level permissions to edit seo defaults', function () {
    $role = Role::make('content-admin')
        ->addPermission('edit seo collections defaults')
        ->addPermission('edit seo taxonomies defaults')
        ->save();

    runPermissionsMigrationScript();

    $role = Role::find('content-admin');

    expect($role->hasPermission('edit seo defaults'))->toBeTrue();
    expect($role->hasPermission('edit seo collections defaults'))->toBeFalse();
    expect($role->hasPermission('edit seo taxonomies defaults'))->toBeFalse();
});

it('removes deprecated view permissions without granting edit or configure permissions', function () {
    $role = Role::make('viewer')
        ->addPermission('view seo collections defaults')
        ->save();

    runPermissionsMigrationScript();

    $role = Role::find('viewer');

    expect($role->hasPermission('configure seo'))->toBeFalse();
    expect($role->hasPermission('edit seo defaults'))->toBeFalse();
    expect($role->hasPermission('view seo collections defaults'))->toBeFalse();
});

it('grants edit seo content to all roles for backward compatibility', function () {
    $role = Role::make('basic')->save();

    runPermissionsMigrationScript();

    $role = Role::find('basic');

    expect($role->hasPermission('edit seo content'))->toBeTrue();
});
