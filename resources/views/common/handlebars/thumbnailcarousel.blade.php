<script>
    /**
     * Converts a received row to a carousel of thumbnail images.
     * @returns {*}
     */
    function handlebarsThumbnailCarouselParse(row) {
        let template = Handlebars.templates['thumbnail_carousel'];

        let items = [];

        console.log(row.dungeon);

        if (row.has_thumbnail) {
            let facadeEnabled = row.dungeon.floors[row.dungeon.floors.length - 1].facade;
            for (let index in row.dungeon.floors) {
                let floor = row.dungeon.floors[index];
                if (!floor.active || ((facadeEnabled && !floor.facade) || (!facadeEnabled && floor.facade))) {
                    continue;
                }

                if (row.png_thumbnails) {
                    items.push({
                        src: `/images/route_thumbnails/${row.public_key}_${floor.index}.png`
                    });
                } else {
                    items.push({
                        src: `/images/route_thumbnails/${row.public_key}_${floor.index}.jpg`
                    });
                }
            }
        } else {
            items.push({
                src: `/images/dungeons/${row.dungeon.expansion.shortname}/${row.dungeon.key}_3-2.jpg`
            });
        }

        let handlebarsData = {
            items: items
        };

        return template(handlebarsData);
    }
</script>
