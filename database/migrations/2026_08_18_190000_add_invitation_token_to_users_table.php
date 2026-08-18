<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Stores a sha256 of the token; the plaintext only ever exists in
            // the emailed URL, so a database leak does not hand over pending
            // accounts. Lookups hash the presented token and match on that.
            $table->string('invitation_token')->nullable()->unique()->after('password');
            $table->timestamp('invitation_sent_at')->nullable()->after('invitation_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['invitation_token']);
            $table->dropColumn(['invitation_token', 'invitation_sent_at']);
        });
    }
};
