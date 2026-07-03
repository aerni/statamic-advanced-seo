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

        Schema::create('redirects', function ($table) {
            $table->string('id')->primary();
            $table->string('source');
            $table->text('destination')->nullable();
            $table->integer('response_code')->default(301);
            $table->string('site');
            $table->boolean('enabled')->default(true);
            $table->boolean('forward_query_string')->nullable()->default(true);
            $table->boolean('automatic')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['site', 'enabled', 'source']);
        });

        Schema::create('redirect_hits', function ($table) {
            $table->string('redirect')->primary();
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }
}
