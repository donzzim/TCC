<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_page_articles')) {
            return;
        }

        Schema::create('cms_page_articles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('cms_page_id');
            $table->unsignedBigInteger('setor_id')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->string('title');
            $table->string('author');
            $table->string('publication_type')->default('publicacao');
            $table->string('issn', 20)->nullable();
            $table->string('doi')->nullable();
            $table->string('volume')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('pages')->nullable();
            $table->string('pdf_path');
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['cms_page_id', 'is_published']);
            $table->index(['year', 'month', 'sort_order']);
        });

        if (Schema::hasTable('cms_pages') && Schema::hasTable('setores') && Schema::hasTable('users')) {
            Schema::table('cms_page_articles', function (Blueprint $table): void {
                $table->foreign('cms_page_id')->references('id')->on('cms_pages')->cascadeOnDelete();
                $table->foreign('setor_id')->references('id')->on('setores')->nullOnDelete();
                $table->foreign('atualizado_por')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_page_articles');
    }
};
