<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('preferences')->nullable();
            $table->timestamp('last_login')->nullable();
        });

        Schema::create('statamic_role_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('role_id');
            $table->unique(['user_id', 'role_id'], 'statamic_role_user_unique');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::create('statamic_group_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('group_id');
            $table->unique(['user_id', 'group_id'], 'statamic_group_user_unique');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statamic_group_user');

        Schema::dropIfExists('statamic_role_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferences', 'last_login']);
        });
    }
};