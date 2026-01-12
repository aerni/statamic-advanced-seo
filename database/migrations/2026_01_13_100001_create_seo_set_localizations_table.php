<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix('seo_set_localizations'), function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('handle');
            $table->string('locale');
            $table->jsonb('data');
            $table->timestamps();

            $table->unique(['type', 'handle', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix('seo_set_localizations'));
    }
};
