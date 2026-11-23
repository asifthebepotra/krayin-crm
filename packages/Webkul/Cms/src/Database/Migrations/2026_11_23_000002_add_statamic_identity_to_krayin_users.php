<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'preferences')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('preferences')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'last_login')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_login')->nullable();
            });
        }

        Schema::create('statamic_role_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('role_id', 100);
            $table->unique(['user_id', 'role_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('statamic_group_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('group_id', 100);
            $table->unique(['user_id', 'group_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statamic_group_user');
        Schema::dropIfExists('statamic_role_user');

        if (Schema::hasColumn('users', 'last_login')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('last_login');
            });
        }

        if (Schema::hasColumn('users', 'preferences')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('preferences');
            });
        }
    }
};
