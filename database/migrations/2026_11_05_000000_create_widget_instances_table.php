<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('widgets.table', 'widget_instances'), function (Blueprint $table) {
            $table->id();
            $table->string('location')->index();
            $table->string('type');
            $table->json('settings')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['location', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('widgets.table', 'widget_instances'));
    }
};
