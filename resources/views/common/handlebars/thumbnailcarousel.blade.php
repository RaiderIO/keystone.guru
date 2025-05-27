<script>
    /**
     * Converts a received row to a carousel of thumbnail images.
     * @returns {*}
     */
    function handlebarsThumbnailCarouselParse(row) {
        let template = Handlebars.templates['thumbnail_carousel'];

        let items = [];

        if (row.has_thumbnail) {
            for(let index in row.thumbnails ){
                let thumbnail = row.thumbnails[index];

                items.push({
                    src: thumbnail.url
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
