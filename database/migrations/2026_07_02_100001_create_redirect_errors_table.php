<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix('redirect_errors'), function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('url');
            $table->string('site');
            $table->unsignedInteger('count')->default(0);
            $table->unsignedInteger('first_seen_at')->nullable();
            $table->unsignedInteger('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['url', 'site']);
            $table->index(['count', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix('redirect_errors'));
    }
};
