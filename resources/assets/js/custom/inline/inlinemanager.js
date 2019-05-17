class InlineManager {


    constructor() {
        //  Array containing all inline code instances
        this._inlineCode = [];
    }

    /**
     * Get the loaded inline code for a specific blade's path.
     * @param bladePath
     * @returns {boolean}
     */
    getInlineCode(bladePath) {
        let result = false;

        for (let index in this._inlineCode) {
            let code = this._inlineCode[index];
            if (code.path === bladePath) {
                result = code.code;
            }
        }

        return result;
    }

    /**
     * Activates a specific piece of inline code for a blade file.
     * @param bladePath
     * @param options
     */
    activate(bladePath, options) {
        console.log(bladePath, options);

        let explode = bladePath.split('/');
        // Upper case all sections
        for (let i = 0; i < explode.length; i++) {
            explode[i] = explode[i][0].toUpperCase() + explode[i].substring(1);
        }
        let className = explode.join('');

        // Bit of a dirty solution, but this works. This creates an instance of the class that is described in the string
        let code = new (eval(className))(options);
        // Now that we have the instance, run the activate function to trigger it
        code.activate();

        this._inlineCode.push({path: bladePath, code: code});

        return code;
    }
}