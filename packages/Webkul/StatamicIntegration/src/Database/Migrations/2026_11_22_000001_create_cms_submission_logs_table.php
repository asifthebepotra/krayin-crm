<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_submission_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('statamic_submission_id')->unique();
            $table->string('form_handle', 100)->index();
            $table->unsignedInteger('crm_lead_id')->nullable()->index();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_submission_logs');
    }
};