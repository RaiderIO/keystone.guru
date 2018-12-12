<?php

?>
<script id="thumbnailcarousel_template" type="text/x-handlebars-template">
    <div class="owl-carousel owl-theme" style="width: 192px; height: 128px">
        @{{#items}}
        <img src="@{{src}}"/>
        @{{/items}}
    </div>
</script>
<script>
    /**
     * Converts a received row to a carousel of thumbnail images.
     * @returns {*}
     */
    function handlebarsThumbnailCarouselParse(row) {
        let thumbnailTemplate = $('#thumbnailcarousel_template').html();
        let template = handlebars.compile(thumbnailTemplate);

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
