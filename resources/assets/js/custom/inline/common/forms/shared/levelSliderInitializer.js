class LevelSliderInitializer {
    constructor(options) {
        // Level
        this.levelHandler = (new KeyLevelHandler(options.levelMin, options.levelMax));


        this.levelHandler.apply(options.levelSelector, {
            min: options.keyLevelMinDefault,
            max: options.keyLevelMaxDefault,
            from: options.levelFrom,
            to: options.levelTo,
            onFinish: function () {
                Cookies.set('route_key_level', $(options.levelSelector).val());
            }
        });
    }

    update(min, max) {
        this.levelHandler.update(min, max);
    }
}
