/**
 * Lists routes through /ajax/routes, which speaks the DataTables server-side protocol: the filters' flat search params
 * are turned into the columns, paging and extra parameters that endpoint reads.
 */
class SearchHandlerDungeonRoutePicker extends SearchHandler {
    getSearchUrl() {
        console.assert(this instanceof SearchHandlerDungeonRoutePicker, 'this is not a SearchHandlerDungeonRoutePicker', this);

        return this.options.listUrl;
    }

    getAjaxOptions() {
        return {
            dataType: 'json',
            cache: false,
        };
    }

    /**
     * @param searchParams {SearchParams}
     * @returns {Object}
     */
    getRequestData(searchParams) {
        let params = searchParams.params;
        let column = function (data, name, searchValue, orderable) {
            return {
                data: data,
                name: name,
                searchable: 'true',
                orderable: orderable ? 'true' : 'false',
                search: {value: searchValue, regex: 'false'},
            };
        };

        return $.extend({
            draw: 1,
            start: params.offset,
            length: params.limit,
            columns: [
                column('title', 'title', (params.title ?? '').trim(), true),
                column('dungeon', 'dungeon_id', params.dungeon ?? '', false),
                column('affixes', 'affixes.id', params.affixes ?? [], false),
                column('routeattributes', 'routeattributes.name', params.attributes ?? [], false),
            ],
            order: [{column: 0, dir: 'asc'}],
            search: {value: '', regex: 'false'},
            requirements: params.requirements ?? [],
            tags: params.tags ?? [],
            // The rows draw a route's pull graph, which the endpoint only pays for when asked
            with_pull_forces: 1,
        }, this.options.sourceParameters, this.options.lockedParameters);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchHandlerDungeonRoutePicker};
}
