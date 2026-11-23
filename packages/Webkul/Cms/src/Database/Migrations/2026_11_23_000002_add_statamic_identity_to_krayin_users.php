<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $missing = [];

        if (! Schema::hasColumn('users', 'super')) {
            $missing[] = 'super';
        }

        if (! Schema::hasColumn('users', 'avatar')) {
            $missing[] = 'avatar';
        }

        if (! Schema::hasColumn('users', 'preferences')) {
            $missing[] = 'preferences';
        }

        if (! Schema::hasColumn('users', 'last_login')) {
            $missing[] = 'last_login';
        }

        if ($missing) {
            Schema::table('users', function (Blueprint $table) use ($missing) {
                foreach ($missing as $column) {
                    match ($column) {
                        'super' => $table->boolean('super')->default(false),
                        'avatar' => $table->string('avatar')->nullable(),
                        'preferences' => $table->json('preferences')->nullable(),
                        'last_login' => $table->timestamp('last_login')->nullable(),
                    };
                }
            });
        }

        Schema::create('statamic_role_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('role_id');
            $table->unique(['user_id', 'role_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('statamic_group_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('group_id');
            $table->unique(['user_id', 'group_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statamic_group_user');
        Schema::dropIfExists('statamic_role_user');

        /*
         * Keep the Statamic-compatible employee columns on rollback. Some
         * may have existed before this package was installed; dropping them
         * could destroy user preferences or other extension data.
         */
    }
};
