<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_theme_installations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug', 100);
            $table->string('version', 40);
            $table->string('name');
            $table->string('author')->nullable();
            $table->text('description')->nullable();
            $table->json('manifest_json');
            $table->string('install_path', 500);
            $table->char('checksum', 64);
            $table->timestamp('installed_at');
            $table->timestamps();

            $table->unique(['slug', 'version'], 'cms_theme_slug_version_unique');
        });

        Schema::create('cms_website_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('site_name')->default('My Website');
            $table->string('primary_domain')->nullable();
            $table->string('default_locale', 20)->default('en');
            $table->string('active_theme_slug', 100)->nullable();
            $table->string('active_theme_version', 40)->nullable();
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamps();
        });

        DB::table('cms_website_settings')->insert([
            'id' => 1,
            'site_name' => config('app.name', 'My Website'),
            'default_locale' => config('app.locale', 'en'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_website_settings');

        Schema::dropIfExists('cms_theme_installations');
    }
};