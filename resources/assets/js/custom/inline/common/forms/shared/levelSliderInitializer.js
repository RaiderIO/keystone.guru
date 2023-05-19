class LevelSliderInitializer {
    constructor(options) {
        // Level
        (new LevelHandler(options.levelMin, options.levelMax)
            .apply(options.levelSelector, {
                from: options.levelFrom,
                to: options.levelTo,
                onFinish: function (data) {
                    Cookies.set('route_key_level', $(options.levelSelector).val());
                }
            }));
    }
}
