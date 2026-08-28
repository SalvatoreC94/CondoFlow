<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only meaningful for role=administrator — the paying customer of
            // the SaaS. Tracked manually from the platform panel for now;
            // will be driven by Stripe webhooks once billing is wired up.
            $table->string('subscription_status')->default('trial')->after('status');
            $table->string('subscription_plan')->nullable()->after('subscription_status');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_plan');
            $table->text('subscription_notes')->nullable()->after('subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['subscription_status', 'subscription_plan', 'subscription_ends_at', 'subscription_notes']);
        });
    }
};
