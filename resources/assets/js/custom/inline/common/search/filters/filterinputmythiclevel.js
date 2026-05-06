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
