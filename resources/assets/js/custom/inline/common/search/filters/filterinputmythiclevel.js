class SearchFilterMythicLevel extends SearchFilterKeyLevel {
    getParamsOverride() {
        let split = this.getValue().split(';');
        return {
            'minMythicLevel': parseInt(split[0]),
            'maxMythicLevel': parseInt(split[1]),
        }
    }

    setValueOverride(name, value) {
        let split = this.getValue().split(';');

        if (name === 'minMythicLevel') {
            this.setValue(`${value};${split[1]}`);
        } else if (name === 'maxMythicLevel') {
            this.setValue(`${split[0]};${value}`);
        } else {
            console.error(`Invalid name ${name} for Key level filter override`);
        }
    }

    getDefaultValueOverride(name) {
        let result = 0;

        if (name === 'minMythicLevel') {
            result = this.levelMin;
        } else if (name === 'maxMythicLevel') {
            result = this.levelMax;
        } else {
            console.error(`Invalid name ${name} for Key level filter override`);
        }

        return result;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterMythicLevel};
}
