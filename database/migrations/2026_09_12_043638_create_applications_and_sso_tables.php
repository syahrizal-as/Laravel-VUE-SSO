<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('base_url');
            $table->string('callback_url');
            $table->string('client_id')->unique();
            $table->string('client_secret');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('application_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_in_app')->nullable()->default('user');
            $table->timestamps();

            $table->unique(['application_id', 'user_id']);
        });

        Schema::create('sso_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket', 128)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['ticket', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sso_tickets');
        Schema::dropIfExists('application_user');
        Schema::dropIfExists('applications');
    }
};
