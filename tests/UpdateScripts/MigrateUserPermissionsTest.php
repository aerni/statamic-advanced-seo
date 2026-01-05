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
    $role = Role::make('editor')
        ->addPermission('edit seo general defaults')
        ->addPermission('edit seo indexing defaults')
        ->addPermission('some other permission')
        ->save();

    runPermissionsMigrationScript();

    $role = Role::find('editor');

    expect($role->permissions())->toContain('configure seo');
    expect($role->permissions())->toContain('some other permission');
    expect($role->permissions())->not->toContain('edit seo general defaults');
    expect($role->permissions())->not->toContain('edit seo indexing defaults');
});

it('migrates content-level edit permissions to edit seo', function () {
    $role = Role::make('editor')
        ->addPermission('edit seo collections defaults')
        ->addPermission('edit seo taxonomies defaults')
        ->save();

    runPermissionsMigrationScript();

    $role = Role::find('editor');

    expect($role->permissions())->toContain('edit seo');
    expect($role->permissions())->not->toContain('edit seo collections defaults');
    expect($role->permissions())->not->toContain('edit seo taxonomies defaults');
});

it('grants both configure and edit permissions when role has both site and content permissions', function () {
    $role = Role::make('admin')
        ->addPermission('edit seo general defaults')
        ->addPermission('edit seo collections defaults')
        ->save();

    runPermissionsMigrationScript();

    $role = Role::find('admin');

    expect($role->permissions())->toContain('configure seo');
    expect($role->permissions())->toContain('edit seo');
    expect($role->permissions())->not->toContain('edit seo general defaults');
    expect($role->permissions())->not->toContain('edit seo collections defaults');
});

it('removes view permissions without granting new permissions', function () {
    $role = Role::make('viewer')
        ->addPermission('view seo collections defaults')
        ->addPermission('view seo taxonomies defaults')
        ->save();

    runPermissionsMigrationScript();

    $role = Role::find('viewer');

    expect($role->permissions())->not->toContain('edit seo');
    expect($role->permissions())->not->toContain('configure seo');
    expect($role->permissions())->not->toContain('view seo collections defaults');
    expect($role->permissions())->not->toContain('view seo taxonomies defaults');
});

it('handles all site-level permissions correctly', function () {
    $role = Role::make('site-admin')
        ->addPermission('edit seo general defaults')
        ->addPermission('edit seo indexing defaults')
        ->addPermission('edit seo social_media defaults')
        ->addPermission('edit seo analytics defaults')
        ->addPermission('edit seo favicons defaults')
        ->save();

    runPermissionsMigrationScript();

    $role = Role::find('site-admin');

    // Should get configure seo (only once, not duplicated)
    expect($role->permissions())->toContain('configure seo');
    expect($role->permissions()->filter(fn ($p) => $p === 'configure seo')->count())->toBe(1);

    // All old permissions removed
    expect($role->permissions())->not->toContain('edit seo general defaults');
    expect($role->permissions())->not->toContain('edit seo indexing defaults');
    expect($role->permissions())->not->toContain('edit seo social_media defaults');
    expect($role->permissions())->not->toContain('edit seo analytics defaults');
    expect($role->permissions())->not->toContain('edit seo favicons defaults');
});

it('does not grant permissions when role has no seo permissions', function () {
    $role = Role::make('basic')
        ->addPermission('view entries')
        ->save();

    runPermissionsMigrationScript();

    $role = Role::find('basic');

    expect($role->permissions())->not->toContain('edit seo');
    expect($role->permissions())->not->toContain('configure seo');
    expect($role->permissions())->toContain('view entries');
});

it('handles mixed view and edit permissions correctly', function () {
    $role = Role::make('mixed')
        ->addPermission('view seo general defaults')
        ->addPermission('edit seo collections defaults')
        ->save();

    runPermissionsMigrationScript();

    $role = Role::find('mixed');

    // Does not get configure seo (view permissions don't grant anything)
    expect($role->permissions())->not->toContain('configure seo');

    // Gets edit seo (from edit seo collections defaults)
    expect($role->permissions())->toContain('edit seo');

    // All old permissions removed
    expect($role->permissions())->not->toContain('view seo general defaults');
    expect($role->permissions())->not->toContain('edit seo collections defaults');
});
