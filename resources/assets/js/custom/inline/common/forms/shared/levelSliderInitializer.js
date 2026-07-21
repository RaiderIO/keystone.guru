class LevelSliderInitializer {
    constructor(options) {
        // Level
        this.levelHandler = (new KeyLevelHandler(options.levelMin, options.levelMax));


        this.levelHandler.apply(options.levelSelector, {
            min: options.keyLevelMinDefault,
            max: options.keyLevelMaxDefault,
            from: options.levelFrom,
            to: options.levelTo
        });
    }

    update(min, max) {
        this.levelHandler.update(min, max);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {LevelSliderInitializer};
}
