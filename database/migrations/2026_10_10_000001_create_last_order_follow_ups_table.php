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
        if (!Schema::hasTable('last_order_follow_ups')) {
            Schema::create('last_order_follow_ups', function (Blueprint $table) {
                $table->id();
                // User yang dihubungi (berada di DB sagansa_user — tanpa FK,
                // pola stock_cards.updated_by).
                $table->unsignedBigInteger('user_id')->index();
                // Admin yang melakukan follow-up (auth DB — tanpa FK).
                $table->unsignedBigInteger('followed_up_by')->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('last_order_follow_ups')) {
            Schema::dropIfExists('last_order_follow_ups');
        }
    }
};
