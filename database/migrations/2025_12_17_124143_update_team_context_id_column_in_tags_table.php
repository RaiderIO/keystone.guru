<?php

use App\Models\Tags\TagCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::update(
            sprintf("
                UPDATE tags
                LEFT JOIN dungeon_routes ON tags.model_id = dungeon_routes.id
                SET tags.context_id = dungeon_routes.team_id
                    WHERE tags.tag_category_id = %s
            ", TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM]),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::update(
            sprintf(
                '
                UPDATE tags
                SET tags.context_id = NULL
                WHERE tags.tag_category_id = %d',
                TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM],
            ),
        );
    }
};
