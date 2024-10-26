<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("projects", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignIdFor(User::class, "user_id")
                ->constrained()
                ->onDelete("CASCADE");
            $table
                ->foreignIdFor(Workspace::class, "workspace_id")
                ->constrained()
                ->onDelete("CASCADE");
            $table->string("name", 255);
            $table->mediumText("description");
            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("projects");
    }
};
