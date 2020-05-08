class InlineManager {


    constructor() {
        //  Array containing all inline code instances
        this._inlineCode = [];

        this._activatedInlineCode = [];

        this._dependencies = [];
    }

    /**
     * Checks if this blade path has its dependencies loaded yet
     * @param bladePath
     * @private
     */
    _hasDependenciesLoaded(bladePath) {
        let numDependencies = 0;
        let loadedDependencies = 0;

        // For each script that has OTHER scripts depend on it (parents)
        for (let parent in this._dependencies) {
            if (this._dependencies.hasOwnProperty(parent)) {
                // For each of the scripts that are waiting for parents to activate (children)
                for (let child in this._dependencies[parent]) {
                    // If it's us that are waiting for a parent
                    if (this._dependencies[parent].hasOwnProperty(child) && this._dependencies[parent][child] === bladePath) {
                        numDependencies++;
                        loadedDependencies += this._activatedInlineCode.includes(parent) ? 1 : 0;
                    }
                }
            }
        }

        return numDependencies === loadedDependencies;
    }

    /**
     * Get the loaded inline code for a specific blade's path.
     * @param bladePath
     * @returns {boolean}
     */
    getInlineCode(bladePath) {
        let result = false;

        for (let index in this._inlineCode) {
            if (this._inlineCode.hasOwnProperty(index)) {
                let code = this._inlineCode[index];
                if (code.path === bladePath) {
                    result = code.code;
                    break;
                }
            }
        }

        return result;
    }

    /**
     * Initializes a specific piece of inline code for a blade file; this code is not activated yet!
     * @param bladePath string
     * @param options object
     */
    init(bladePath, options) {
        console.log(bladePath, options);

        let explode = bladePath.split('/');
        // Upper case all sections
        for (let i = 0; i < explode.length; i++) {
            explode[i] = explode[i][0].toUpperCase() + explode[i].substring(1);
        }
        let className = explode.join('');

        // Bit of a dirty solution, but this works. This creates an instance of the class that is described in the string
        let code = new (eval(className))(options);

        this._inlineCode.push({path: bladePath, code: code});

        // If this inline code has dependencies..
        if (typeof options.dependencies === 'object' && options.dependencies !== null) {
            // If the file we depend on did not have any dependencies yet..
            if (typeof this._dependencies[options.dependencies] !== 'object') {
                this._dependencies[options.dependencies] = [];
            }

            // This blade now has a dependency
            this._dependencies[options.dependencies].push(bladePath);
        }

        return code;
    }

    /**
     * Activates all loaded inline code.
     * @param bladePath
     */
    activate(bladePath) {
        if (this._hasDependenciesLoaded(bladePath)) {
            // console.warn(`Loading ${bladePath}, dependencies are loaded`);
            let code = this.getInlineCode(bladePath);
            // Now that we have the instance, run the activate function to trigger it
            code.activate();

            // This is now activated
            this._activatedInlineCode.push(bladePath);

            // If there were any dependencies on this blade..
            let dependencies = this._dependencies[bladePath];
            if (typeof dependencies !== 'undefined') {
                // Attempt to activate everything that depended on it now that we're activated
                for (let index in dependencies) {
                    if (dependencies.hasOwnProperty(index)) {
                        console.log(`Attempting load of ${dependencies[index]} since dependency is now loaded`);
                        this.activate(dependencies[index]);
                    }
                }
            }
        } else {
            console.warn(`Not loading ${bladePath}, dependencies not loaded`, this._dependencies);
        }
    }
}