class InlineManager {


    constructor() {
        //  Array containing all inline code instances
        this._inlineCode = [];

        this._activatedInlineCode = [];

        // Key: this ID depends on these blade paths to be loaded
        this._dependencies = [];
        // Key: these IDs need to activate these IDs when loaded
        this._dependenciesById = [];
    }

    /**
     * Checks if this ID has its dependencies loaded yet
     *
     * @param id {string}
     * @private
     */
    _hasDependenciesLoadedById(id) {
        let numDependencies = 0;
        let loadedDependencies = 0;

        // For each script that has OTHER scripts depend on it (parents)
        for (let parent in this._dependenciesById) {
            // For each of the scripts that are waiting for parents to activate (children)
            for (let child in this._dependenciesById[parent]) {
                // If it's us that are waiting for a parent
                if (this._dependenciesById[parent][child] === id) {
                    numDependencies++;
                    // Check if the dependency is activated
                    for (let id in this._activatedInlineCode) {
                        loadedDependencies += (id === parent ? 1 : 0);
                    }
                }
            }
        }

        return numDependencies === loadedDependencies;
    }

    /**
     * Checks if this blade path has its dependencies loaded yet
     *
     * @param bladePath {string}
     * @private
     */
    _hasDependenciesLoaded(bladePath) {
        let numDependencies = 0;
        let loadedDependencies = 0;

        // For each script that has OTHER scripts depend on it (parents)
        for (let parent in this._dependencies) {
            // For each of the scripts that are waiting for parents to activate (children)
            for (let child in this._dependencies[parent]) {
                // If it's us that are waiting for a parent
                if (this._dependencies[parent][child] === bladePath) {
                    numDependencies++;
                    // Check if the dependency is activated
                    for (let id in this._activatedInlineCode) {
                        loadedDependencies += this._activatedInlineCode[id].bladePath === parent ? 1 : 0;
                    }
                }
            }
        }

        return numDependencies === loadedDependencies;
    }

    /**
     * @param id {string}
     * @returns {InlineCode}
     */
    getInlineCodeById(id) {
        return this._inlineCode[id];
    }

    /**
     * Get the loaded inline code for a specific blade's path.
     *
     * @param bladePath {string}
     * @returns {InlineCode|InlineCode[]}
     */
    getInlineCode(bladePath) {
        let result = [];

        for (let index in this._inlineCode) {
            if (this._inlineCode.hasOwnProperty(index)) {
                let code = this._inlineCode[index];
                if (code.bladePath === bladePath) {
                    result.push(code);
                }
            }
        }

        // Only return an array when we have multiple!
        // A bit of a hack I guess but we should know when we have multiple instances of the same blade
        // This rarely happens
        return result.length === 1 ? result[0] : result;
    }

    /**
     * Initializes a specific piece of inline code for a blade file; this code is not activated yet!
     *
     * @param id {string}
     * @param bladePath {string}
     * @param options {object}
     */
    init(id, bladePath, options) {
        console.log(bladePath, id, options);

        let explode = bladePath.split('/');
        // Upper case all sections
        for (let i = 0; i < explode.length; i++) {
            explode[i] = explode[i][0].toUpperCase() + explode[i].substring(1);
        }
        let className = explode.join('');

        // Bit of a dirty solution, but this works. This creates an instance of the class that is described in the string
        let code = new (eval(className))(id, bladePath, options);

        this._inlineCode[id] = code;

        // If this inline code has dependencies on other paths..
        if (typeof options.dependencies === 'object' && options.dependencies !== null) {
            // For each file that we depend on
            for (let index in options.dependencies) {
                let dependent = options.dependencies[index];

                // If the file we depend on did not have any dependencies yet..
                if (typeof this._dependencies[dependent] !== 'object') {
                    this._dependencies[dependent] = [];
                }

                // Keep track of the fact that WE are waiting for THEM to be loaded
                // This way - if they are loaded we can easily check if we can load ourselves (there may be more dependencies to load first)
                this._dependencies[dependent].push(bladePath);
            }
        }

        // If this inline code has dependencies on other specific IDs
        if (typeof options.dependenciesById === 'object' && options.dependenciesById !== null) {
            // For each file that we depend on
            for (let index in options.dependenciesById) {
                let dependent = options.dependenciesById[index];

                // If the file we depend on did not have any dependencies yet..
                if (typeof this._dependenciesById[dependent] !== 'object') {
                    this._dependenciesById[dependent] = [];
                }

                // Keep track of the fact that WE are waiting for THEM to be loaded
                // This way - if they are loaded we can easily check if we can load ourselves (there may be more dependencies to load first)
                this._dependenciesById[dependent].push(id);
            }
        }

        return code;
    }

    /**
     * Activates loaded inline code.
     *
     * @param id {string|null}
     */
    activate(id) {
        // console.warn(`Loading ${bladePath}, dependencies are loaded`);
        let code = this.getInlineCodeById(id);
        if (!(code instanceof InlineCode)) {
            console.error(`Inline code for ${id} not found!`);
            return;
        }

        if (this._hasDependenciesLoaded(code.bladePath) && this._hasDependenciesLoadedById(id)) {
            // Now that we have the instance, run the activate function to trigger it
            code.activate();

            // This is now activated
            this._activatedInlineCode[code.id] = code;

            // If there were any dependencies on this blade..
            let dependencies = this._dependencies[code.bladePath];
            if (typeof dependencies !== 'undefined') {
                // Attempt to activate everything that depended on it now that we're activated
                for (let index in dependencies) {
                    if (dependencies.hasOwnProperty(index)) {
                        console.log(`Attempting load of ${dependencies[index]} since dependency ${id} is now loaded`);
                        this.activate(dependencies[index]);
                    }
                }
            }

            // If there were any dependencies on this blade..
            let dependenciesById = this._dependenciesById[code.id];
            if (typeof dependenciesById !== 'undefined') {
                // Attempt to activate everything that depended on it now that we're activated
                for (let index in dependenciesById) {
                    if (dependenciesById.hasOwnProperty(index)) {
                        console.log(`Attempting load of ${dependenciesById[index]} since dependency ${id} is now loaded`);
                        this.activate(dependenciesById[index]);
                    }
                }
            }
        } else {
            console.warn(`Not loading ${code.bladePath}, dependencies not loaded`, this._dependencies, this._dependenciesById);
        }
    }
}
