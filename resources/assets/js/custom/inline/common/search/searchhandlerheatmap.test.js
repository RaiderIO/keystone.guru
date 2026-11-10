global.SearchHandler = require('./searchhandler').SearchHandler;
const {SearchHandlerHeatmap} = require('./searchhandlerheatmap');

describe('SearchHandlerHeatmap loaderFn', () => {
    let runCountTemplate;
    let addSnackbar;

    beforeEach(() => {
        runCountTemplate = vi.fn(() => 'run count snackbar');
        addSnackbar = vi.fn(() => 'snackbar-id');
        global.Handlebars = {templates: {map_heatmapsearch_run_count: runCountTemplate}};
        global.getHandlebarsDefaultVariables = () => ({});
        global.getState = () => ({removeSnackbar: vi.fn(), addSnackbar: addSnackbar});
        global.lang = {get: (key, replacements) => `${key}:${replacements?.count}`};
        global.$ = {...global.$, extend: (target, ...sources) => Object.assign(target, ...sources)};
    });

    it('loaderFn_givenTheResponseJQueryParsed_readsItInsteadOfTheResponseText', () => {
        // Arrange
        const searchHandler = new SearchHandlerHeatmap({});

        // Act
        searchHandler.options.loaderFn(false, 'not json', {responseJSON: {run_count: 12}});

        // Assert
        expect(runCountTemplate).toHaveBeenCalledWith({run_count: 'js.run_count_label:12'});
        expect(addSnackbar).toHaveBeenCalledWith('run count snackbar', {compact: true});
    });

    it('loaderFn_givenOnlyTheResponseText_parsesIt', () => {
        // Arrange
        const searchHandler = new SearchHandlerHeatmap({});

        // Act
        searchHandler.options.loaderFn(false, '{"run_count": 7}', {});

        // Assert
        expect(runCountTemplate).toHaveBeenCalledWith({run_count: 'js.run_count_label:7'});
    });
});
