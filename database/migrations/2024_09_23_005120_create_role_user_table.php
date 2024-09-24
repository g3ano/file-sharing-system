<?php

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id')
                ->constrained()
                ->onDelete('CASCADE');
            $table->foreignIdFor(Role::class, 'role_id')
                ->constrained()
                ->onDelete('CASCADE');
            $table->foreignIdFor(Workspace::class, 'workspace_id')
                ->nullable()
                ->constrained()
                ->onDelete('CASCADE');
            $table->foreignIdFor(Project::class, 'project_id')
                ->nullable()
                ->constrained()
                ->onDelete('CASCADE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
