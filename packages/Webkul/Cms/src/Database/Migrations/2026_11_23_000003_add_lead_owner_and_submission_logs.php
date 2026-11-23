<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_settings', function (Blueprint $table) {
            $table->unsignedInteger('default_lead_owner_id')
                ->nullable()
                ->after('timezone');

            $table->foreign('default_lead_owner_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('cms_submission_logs', function (Blueprint $table) {
            $table->id();
            $table->string('form_handle', 100);
            $table->string('submission_id', 64);
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('crm_lead_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['form_handle', 'submission_id'],
                'cms_submission_form_id_unique'
            );

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_submission_logs');

        Schema::table('cms_settings', function (Blueprint $table) {
            $table->dropForeign(['default_lead_owner_id']);
            $table->dropColumn('default_lead_owner_id');
        });
    }
};
