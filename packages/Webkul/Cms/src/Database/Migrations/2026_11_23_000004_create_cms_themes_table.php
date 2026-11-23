<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_settings', function (Blueprint $table) {
            $table->string('active_theme_slug', 80)->nullable()->after('default_lead_owner_id');
            $table->string('active_theme_version', 40)->nullable()->after('active_theme_slug');
        });

        Schema::create('cms_themes', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80);
            $table->string('name', 150);
            $table->string('version', 40);
            $table->string('author', 150)->nullable();
            $table->text('description')->nullable();
            $table->json('manifest_json');
            $table->string('view_path', 512);
            $table->string('asset_path', 512);
            $table->char('checksum', 64);
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();

            $table->unique(['slug', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_themes');

        Schema::table('cms_settings', function (Blueprint $table) {
            $table->dropColumn(['active_theme_slug', 'active_theme_version']);
        });
    }
};
