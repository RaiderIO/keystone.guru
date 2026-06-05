---
name: javascript-in-blade-files
description: Helps writing JavaScript in blade templates in a project-compliant way. Use when the user wants to write or edit Javascript in Blade template. Also use when Inline or Inline Code is mentioned, or "longer" scripts need to be written for inclusion in Blade templates.
---


# Javascript in blade files

Helps writing JavaScript for blade files in a project-compliant way.

## System Prompt

You are a helpful assistant that writes JavaScript for blade files in a project-compliant way. You understand the project's coding standards and best practices for integrating JavaScript into blade templates. You can write clean, efficient, and maintainable JavaScript code that works seamlessly with the blade files.

## Short scripts (directly in blade files)

When writing JavaScript for blade files, short scripts can be included directly in the blade file. A short script is roughly 20 lines long or less, and is simple enough to be easily understood when included directly in the blade file.
This is useful for small scripts that are specific to a single blade file and do not need to be reused elsewhere.

If your script is longer than 20 lines, consider putting it in an Inline Code instead.

A short script can be something like this:

```blade
@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_dungeon_floor_table').DataTable({
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {

                })
            });
        });
    </script>
@endsection
```

## Long scripts (Inline Code)

All JavaScript file names in the `resources/assets/js/custom/inline` folder are lower case. This is important for the Inline Code Manager to be able to find and load the scripts correctly.

### Inline Code folder structure

resources/assets/js/custom/inline
├── inlinemanager.js (manages and loads inline code scripts for blade files with dependency manager)
├── inlinecode.js (base class for inline code scripts)
└── others (each folder/file exactly matches the blade file structure for easy reference, and contains the JavaScript code for the blade file)

### Benefits of using Inline Code for longer scripts

Inline Code script is compiled to a single JavaScript file, which can be cached by the CDN. Short scripts are not compiled and are included directly in the blade file, which can lead to increased data usage.

Inline Code scripts can depend on one another and are loaded in the correct order by the Inline Code Manager.

### Adding new Inline Code scripts

After adding a new Inline Code script, `npm run watch` needs to be restarted for the new script to be compiled and available for use in blade files. Notify the user they need to do this whenever you create a new Inline Code script.

### Example of how to include an Inline Code script in a blade file

```bladehtml
@include('common.general.inline', [
    'path' => 'common/modal/mappingversion',
    'options' => [
        'saveMappingVersionSelector' => '#save_mapping_version'
    ]
])
```

The `path` option is the path to the Inline Code script, relative to the `resources/assets/js/custom/inline` folder, without the `.js` extension. The `options` option is an object that contains any variables that need to be passed to the Inline Code script from PHP.

A blade file can include multiple Inline Code scripts if needed. Any secondary Inline Code scripts should be an Inline Code that exists in the `common` directory, as these are meant to be reusable across multiple blade files.

Dependencies can also be specified for an Inline Code script, which will ensure that the dependent scripts are loaded before the script that depends on them.

For example:

```bladehtml
@include('common.general.inline', ['path' => 'dungeon/explore/gameversion/embed', 'options' => [
    'dependencies' => ['common/maps/map'],
]])

@include('common.general.inline', ['path' => 'common/maps/embedtopbar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'switchDungeonFloorSelect' => '#map_floor_selection_dropdown',
    'defaultSelectedFloorId' => $floor->id,
    'mdtStringCopyEnabled' => false,
]])
```

### Example of an Inline Code script

```javascript
/**
 @typedef {Object} CommonModalMappingversionOptions
 @property {string} saveMappingVersionSelector
 @property {Number} lng
 @property {Number} floor_id
 */

/**
 * @property {CommonModalMappingversionOptions} options
 */
class CommonModalMappingversion extends InlineCode {

    activate() {
        // Save settings in the modal
        $(this.options.saveMappingVersionSelector).unbind('click').bind('click', this._saveMappingVersion);
    }

    /**
     *
     * @private
     */
    _saveMappingVersion() {
        $.ajax({
            ...
        });
    }
}
```

### Inline Code script naming conventions

The class name of the Inline Code script should be in PascalCase, where each folder and file name is capitalized and concatenated together. The file name itself should start with a capital letter and all lower case after that.

For example:

- Blade file located at `resources/views/common/modal/mappingversion.blade.php`
  - Inline Code script location `resources/assets/js/custom/inline/common/modal/mappingversion.js`
  - Class name `CommonModalMappingversion`.
- Blade file located at `resources/views/dungeonroute/edit.blade.php`
    - Inline Code script location `resources/assets/js/custom/inline/dungeonroute/edit.js`
    - Class name `DungeonrouteEdit`.
- Blade file located at `resources/views/common/dungeonroute/coverage/affixgroup.blade.php`
    - Inline Code script location `resources/assets/js/custom/inline/common/dungeonroute/coverage/affixgroup.js`
    - Class name `CommonDungeonrouteCoverageAffixgroup`.

### Passing variables to Inline Code scripts

Variables can be passed to Inline Code scripts from the blade file using the `options` parameter of the `@include` directive. This is the only valid way to pass variables from PHP to the JavaScript code in the blade file.

CSS selectors should be passed as options to the Inline Code script, and not hardcoded in the JavaScript code. This allows for greater flexibility and reusability of the JavaScript code across different blade files.

### Inline Code script file structure

The `activate()` method is called by the Inline Code Manager when the script should be activated. This is where you should put any code that needs to be executed once when the "page loads" for your blade file, after any dependencies are already loaded.

### Modifying or refactoring existing Inline Code scripts

When modifying or refactoring existing Inline Code scripts, try to clean up the code as much as possible and make it more maintainable.

- Any CSS selectors should be passed through the options object, and not hardcoded in the JavaScript code.
- Create a new @typedef for the `options` object that the script receives, and use it to type hint the `options` property of the class.
- If an options object is missing, add it
