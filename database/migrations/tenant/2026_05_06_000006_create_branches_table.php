<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branches', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignId('level_id')->constrained()->onDelete('no action');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->integer('position')->unsigned();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('branches')
                ->onDelete('set null');
        });

        Schema::create('branches_tree', static function (Blueprint $table): void {
            $table->increments('closure_id');
            $table->integer('ancestor_id')->unsigned();
            $table->integer('descendant_id')->unsigned();
            $table->integer('depth')->unsigned();

            $table->foreign('ancestor_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');

            $table->foreign('descendant_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches_tree');
        Schema::dropIfExists('branches');
    }
};
