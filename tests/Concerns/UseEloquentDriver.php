<?php

namespace Aerni\AdvancedSeo\Tests\Concerns;

use Aerni\AdvancedSeo\Enums\RedirectOrigin;
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
            $table->text('source');
            $table->string('source_hash', 32);
            $table->string('source_type');
            $table->text('destination')->nullable();
            $table->integer('response_code')->default(301);
            $table->string('site');
            $table->boolean('enabled')->default(true);
            $table->boolean('preserve_query_string')->nullable()->default(true);
            $table->string('origin')->default(RedirectOrigin::Manual->value);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['site', 'enabled', 'source_type']);
            $table->unique(['source_hash', 'site']);
        });

        Schema::create('redirect_hits', function ($table) {
            $table->string('redirect')->primary();
            $table->unsignedInteger('count')->default(0);
            $table->unsignedInteger('last_hit_at')->nullable();
            $table->timestamps();
        });

        Schema::create('redirect_errors', function ($table) {
            $table->string('id')->primary();
            $table->text('url');
            $table->string('url_hash', 32);
            $table->string('site');
            $table->unsignedInteger('count')->default(0);
            $table->unsignedInteger('first_seen_at')->nullable();
            $table->unsignedInteger('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['url_hash', 'site']);
            $table->index(['count', 'last_seen_at']);
        });
    }
}
