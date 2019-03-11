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

        let code = new className();
        console.log(code);
    }
}