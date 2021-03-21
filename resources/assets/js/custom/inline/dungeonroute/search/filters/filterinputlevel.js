class SearchFilterLevel extends SearchFilterInput {
    constructor(selector, onChange, levelMin, levelMax) {
        super({
            name: 'level',
            default: '',
            selector: selector,
            onChange: onChange
        });

        this.levelMin = levelMin;
        this.levelMax = levelMax;
    }

    activate() {
        super.activate();

        let self = this;

        // Level
        (new LevelHandler(this.levelMin, this.levelMax).apply(this.options.selector, {
            onFinish: function (data) {
                self.options.onChange();
            }
        }));
    }
}