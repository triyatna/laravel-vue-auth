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
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('min_exp');
            $table->unsignedBigInteger('max_exp')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->default('#000000');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('level_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained('levels')->onDelete('cascade');
            $table->enum('benefit_type', ['discount', 'cashback', 'point_multiplier', 'priority_support', 'birthday_gift']);
            $table->decimal('value', 15, 2)->default(0);
            $table->enum('value_type', ['percent', 'nominal', 'multiplier'])->default('percent');
            $table->enum('limit_period', ['daily', 'weekly', 'monthly', 'yearly', 'lifetime'])->default('monthly');
            $table->integer('usage_limit')->default(0);
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('user_unique')->index();
            $table->decimal('balance', 15, 2)->default(0);
            $table->unsignedBigInteger('point')->default(0);
            $table->foreignId('level_id')->nullable()->constrained('levels')->nullOnDelete();
            $table->enum('privilege', ['member', 'reseller', 'vip'])->default('member');
            $table->unsignedBigInteger('exp')->default(0);
            $table->string('pin', 10)->nullable();
            $table->enum('type', ['production', 'development'])->default('production');
            $table->enum('status', ['active', 'inactive', 'suspended', 'locked'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_unique')->references('user_unique')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levels');
        Schema::dropIfExists('level_benefits');
        Schema::dropIfExists('users_wallets');
    }
};
