<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix('redirect_hits'), function (Blueprint $table) {
            $table->string('redirect')->primary();
            $table->unsignedInteger('count')->default(0);
            $table->unsignedInteger('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix('redirect_hits'));
    }
};
