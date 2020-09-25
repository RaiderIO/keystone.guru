<script>
    /**
     * Converts a received row to a carousel of thumbnail images.
     * @returns {*}
     */
    function handlebarsThumbnailCarouselParse(row) {
        let template = Handlebars.templates['thumbnailcarousel_template'];

        let items = [];

        if( row.has_thumbnail ) {
            for (let i = 1; i <= row.dungeon.floor_count; i++) {
                items.push({
                    src: '/images/route_thumbnails/' + row.public_key + '_' + i + '.png'
                });
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
