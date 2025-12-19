<?php

use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Tag::where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])
            ->update(['context_class' => 'App\\Models\\User']);
        Tag::where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM])
            ->update(['context_class' => 'App\\Models\\Team']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::update('UPDATE tags SET context_class = NULL WHERE 1=1');
    }
};
