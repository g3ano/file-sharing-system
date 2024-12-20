<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("files", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignIdFor(User::class, "user_id")
                ->constrained()
                ->onDelete("CASCADE");
            $table
                ->foreignIdFor(Project::class, "project_id")
                ->constrained()
                ->onDelete("CASCADE");
            $table->string("name");
            $table->string("extension");
            $table->string("type");
            $table->integer("size");
            $table->string("path");
            $table->string("hash");
            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("files");
    }
};
