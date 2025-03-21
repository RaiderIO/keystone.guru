class DungeonExploreGameversionEmbed extends InlineCode {

    /**
     */
    activate() {
        super.activate();

        let validHostnames = [
            'localhost',
            'keystone.guru',
            'raider.io',
            'raiderio.dev',
        ];

        window.addEventListener('message', (event) => {
            let hostname = (new URL(event.origin)).hostname;

            let isValidHostname = false;
            for (let index in validHostnames) {
                let validHostname = validHostnames[index];
                if (hostname.endsWith(`.${validHostname}`) || hostname === validHostname) {
                    isValidHostname = true;
                    break;
                }
            }

            if (!isValidHostname) {
                console.warn('Invalid hostname - not processing message!');
                return false;
            }

            // Maybe this message is not for us?
            if (typeof event.data.function === 'undefined' || event.data.function === null) {
                return false;
            }

            if (event.data.function === 'setFilters') {
                /** @type CommonMapsHeatmapsearchsidebar|false */
                let inlineCode = _inlineManager.getInlineCode('common/maps/heatmapsearchsidebar');
                if (!inlineCode) {
                    console.error('Unable to find sidebar!');
                    return false;
                }

                delete event.data.function;

                console.log('Applying filters', event.data);
                inlineCode.searchWithFilters(event.data);
            } else {
                console.warn('Unknown function', event.data.function);
            }

            return true;
        });
    }

    cleanup() {
    }
}
