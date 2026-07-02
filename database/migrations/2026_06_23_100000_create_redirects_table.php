<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix('redirects'), function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('source');
            $table->text('destination')->nullable();
            $table->integer('type')->default(301);
            $table->string('site');
            $table->boolean('enabled')->default(true);
            $table->boolean('forward_query_string')->nullable()->default(true);
            $table->boolean('automatic')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['site', 'enabled', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix('redirects'));
    }
};
