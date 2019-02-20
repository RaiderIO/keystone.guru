<script>
    /**
     * Converts a received row to a carousel of thumbnail images.
     * @returns {*}
     */
    function handlebarsThumbnailCarouselParse(row) {
        let template = Handlebars.templates['thumbnailcarousel_template'];

        let items = [];

        for (let i = 1; i <= row.dungeon.floor_count; i++) {
            items.push({
                src: '/images/route_thumbnails/' + row.public_key + '_' + i + '.png'
            });
        }

        let handlebarsData = {
            items: items
        };

        return template(handlebarsData);
    }
</script>
