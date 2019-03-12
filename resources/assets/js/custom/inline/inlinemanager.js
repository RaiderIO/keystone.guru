class InlineManager {


    constructor() {

    }

    /**
     * Activates a specific piece of inline code for a blade file.
     * @param bladePath
     */
    activate(bladePath) {
        let explode = bladePath.split('/');
        // Upper case all sections
        for (let i = 0; i < explode.length; i++) {
            explode[i] = explode[i][0].toUpperCase() + explode[i].substring(1);
        }
        let className = explode.join('');

        // Bit of a dirty solution, but this works. This creates an instance of the class that is described in the string
        let code = new (eval(className));
        // Now that we have the instance, run the activate function to trigger it
        code.activate();
    }
}