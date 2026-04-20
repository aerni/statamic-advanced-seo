<?php

namespace Aerni\AdvancedSeo\Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

trait UseEloquentDriver
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('seo_set_configs', function ($table) {
            $table->id();
            $table->string('type');
            $table->string('handle');
            $table->json('data');
            $table->timestamps();

            $table->unique(['type', 'handle']);
        });

        Schema::create('seo_set_localizations', function ($table) {
            $table->id();
            $table->string('type');
            $table->string('handle');
            $table->string('locale');
            $table->json('data');
            $table->timestamps();

            $table->unique(['type', 'handle', 'locale']);
        });
    }
}
