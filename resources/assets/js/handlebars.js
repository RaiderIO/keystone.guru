(function() {
  var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
templates['affixgroup_select_option_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper;

  return "        <div class=\"col affix_row\">\n            <div class=\"select_icon affix_icon_"
    + container.escapeExpression(((helper = (helper = helpers["class"] || (depth0 != null ? depth0["class"] : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"class","hash":{},"data":data}) : helper)))
    + "\" style=\"height: 24px;\"> </div>\n        </div>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, buffer = 
  "<div class=\"row affix_row_container no-gutters\">\n";
  stack1 = ((helper = (helper = helpers.affixes || (depth0 != null ? depth0.affixes : depth0)) != null ? helper : helpers.helperMissing),(options={"name":"affixes","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!helpers.affixes) { stack1 = helpers.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>";
},"useData":true});
templates['affixgroups_complex_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, buffer = 
  "    <div class=\"row affix_list_row no-gutters\" style=\"width: 200px\">\n";
  stack1 = ((helper = (helper = helpers.affixes || (depth0 != null ? depth0.affixes : depth0)) != null ? helper : helpers.helperMissing),(options={"name":"affixes","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!helpers.affixes) { stack1 = helpers.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "    </div>\n";
},"2":function(container,depth0,helpers,partials,data) {
    var helper;

  return "        <div class=\"affix_row col-md-3\">\n            <div class=\"select_icon affix_icon_"
    + container.escapeExpression(((helper = (helper = helpers["class"] || (depth0 != null ? depth0["class"] : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"class","hash":{},"data":data}) : helper)))
    + " mr-2\" style=\"height: 24px;\">\n                &nbsp;\n            </div>\n        </div>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression, buffer = 
  "<span class=\"target_tooltip\" data-toggle=\"tooltip\" data-html=\"true\">\n    "
    + alias4(((helper = (helper = helpers.count || (depth0 != null ? depth0.count : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"count","hash":{},"data":data}) : helper)))
    + " "
    + alias4(((helper = (helper = helpers.selected_label || (depth0 != null ? depth0.selected_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"selected_label","hash":{},"data":data}) : helper)))
    + "\n</span>\n<?php // Wrapper so we can put all this in the tooltip of the above span. I'm not cramming that in a tiny attribute manually ?>\n<div class=\"affix_list_row_container\">\n";
  stack1 = ((helper = (helper = helpers.affixgroups || (depth0 != null ? depth0.affixgroups : depth0)) != null ? helper : alias2),(options={"name":"affixgroups","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!helpers.affixgroups) { stack1 = helpers.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>";
},"useData":true});
templates['affixgroups_single_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, buffer = 
  "<div class=\"affix_list_row row no-gutters\">\n";
  stack1 = ((helper = (helper = helpers.affixes || (depth0 != null ? depth0.affixes : depth0)) != null ? helper : helpers.helperMissing),(options={"name":"affixes","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!helpers.affixes) { stack1 = helpers.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>\n";
},"2":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "    <div class=\"affix_row float-left\">\n        <div class=\"select_icon affix_icon_"
    + alias4(((helper = (helper = helpers["class"] || (depth0 != null ? depth0["class"] : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"class","hash":{},"data":data}) : helper)))
    + " mr-2\" style=\"height: 24px;\" data-toggle=\"tooltip\"\n             title=\""
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "\">\n            &nbsp;\n        </div>\n    </div>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options;

  stack1 = ((helper = (helper = helpers.affixgroups || (depth0 != null ? depth0.affixgroups : depth0)) != null ? helper : helpers.helperMissing),(options={"name":"affixgroups","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!helpers.affixgroups) { stack1 = helpers.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { return stack1; }
  else { return ''; }
},"useData":true});
templates['app_fixed_footer_small_template'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"text-center m-1\">\n    <span class=\"alert alert-"
    + alias4(((helper = (helper = helpers.type || (depth0 != null ? depth0.type : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"type","hash":{},"data":data}) : helper)))
    + " border-secondary mb-0\">\n        "
    + alias4(((helper = (helper = helpers.message || (depth0 != null ? depth0.message : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"message","hash":{},"data":data}) : helper)))
    + "\n    </span>\n</div>";
},"useData":true});
templates['app_fixed_footer_template'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"alert alert-"
    + alias4(((helper = (helper = helpers.type || (depth0 != null ? depth0.type : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"type","hash":{},"data":data}) : helper)))
    + " mb-0 text-center border-secondary border-top m-1\">\n    "
    + alias4(((helper = (helper = helpers.message || (depth0 != null ? depth0.message : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"message","hash":{},"data":data}) : helper)))
    + "\n</div>";
},"useData":true});
templates['biglistfeatures_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function";

  return "    <div class=\"row no-gutters mt-1 d-none d-md-flex\">\n        <div class=\"col-xl-5\">"
    + container.escapeExpression(((helper = (helper = helpers.attributes_label || (depth0 != null ? depth0.attributes_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"attributes_label","hash":{},"data":data}) : helper)))
    + ":</div>\n        <div class=\"col-xl-7\">\n            "
    + ((stack1 = ((helper = (helper = helpers.attributes || (depth0 != null ? depth0.attributes : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"attributes","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\n        </div>\n    </div>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"row no-gutters mt-1\">\n    <div class=\"col-xl-5 d-none d-md-flex\">"
    + alias4(((helper = (helper = helpers.affixes_label || (depth0 != null ? depth0.affixes_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"affixes_label","hash":{},"data":data}) : helper)))
    + ":</div>\n    <div class=\"col-xl-7\">\n        "
    + ((stack1 = ((helper = (helper = helpers.affixes || (depth0 != null ? depth0.affixes : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"affixes","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\n    </div>\n</div>\n\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.showAttributes : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "<?php /*Hidden on smaller screens*/ ?>\n<div class=\"row no-gutters mt-1 d-none d-lg-flex\">\n    <div class=\"col-xl-5\">"
    + alias4(((helper = (helper = helpers.setup_label || (depth0 != null ? depth0.setup_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"setup_label","hash":{},"data":data}) : helper)))
    + ":</div>\n    <div class=\"col-xl-7\">\n        "
    + ((stack1 = ((helper = (helper = helpers.setup || (depth0 != null ? depth0.setup : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"setup","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\n    </div>\n</div>";
},"useData":true});
templates['composition_icon_option_template'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"row no-gutters\">\n    <div class=\"col-auto select_icon class_icon "
    + alias4(((helper = (helper = helpers.css_class || (depth0 != null ? depth0.css_class : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"css_class","hash":{},"data":data}) : helper)))
    + "\" style=\"height: 24px;\">\n    </div>\n    <div class=\"col pl-1\">\n        "
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "\n    </div>\n</div>";
},"useData":true});
templates['dungeonroute_table_profile_actions_template'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"row no-gutters\">\n    <div class=\"col\">\n        <div class=\"btn btn-danger dungeonroute-clone\"\n             data-publickey=\""
    + alias4(((helper = (helper = helpers.public_key || (depth0 != null ? depth0.public_key : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"public_key","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.clone_label || (depth0 != null ? depth0.clone_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"clone_label","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n    <div class=\"col mt-2 mt-xl-0\">\n        <div class=\"btn btn-danger dungeonroute-delete\"\n             data-publickey=\""
    + alias4(((helper = (helper = helpers.public_key || (depth0 != null ? depth0.public_key : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"public_key","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.delete_label || (depth0 != null ? depth0.delete_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"delete_label","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n</div>";
},"useData":true});
templates['group_setup_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "    <div class=\"col-auto select_icon mr-2 class_icon "
    + alias4(((helper = (helper = helpers.css_class || (depth0 != null ? depth0.css_class : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"css_class","hash":{},"data":data}) : helper)))
    + "\" style=\"height: 24px;\" data-toggle=\"tooltip\"\n         title=\""
    + alias4(((helper = (helper = helpers.title || (depth0 != null ? depth0.title : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title","hash":{},"data":data}) : helper)))
    + "\">\n    </div>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression, buffer = 
  "<div class=\"row no-gutters\">\n    <div class=\"col-auto select_icon mr-2 "
    + alias4(((helper = (helper = helpers.css_class || (depth0 != null ? depth0.css_class : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"css_class","hash":{},"data":data}) : helper)))
    + "\" style=\"height: 24px;\" data-toggle=\"tooltip\"\n         title=\""
    + alias4(((helper = (helper = helpers.faction_title || (depth0 != null ? depth0.faction_title : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"faction_title","hash":{},"data":data}) : helper)))
    + "\">\n    </div>\n";
  stack1 = ((helper = (helper = helpers.classes || (depth0 != null ? depth0.classes : depth0)) != null ? helper : alias2),(options={"name":"classes","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!helpers.classes) { stack1 = helpers.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>";
},"useData":true});
templates['import_string_details_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"row no-gutters\">\n    <div class=\"col-4 font-weight-bold\">\n        "
    + alias4(((helper = (helper = helpers.key || (depth0 != null ? depth0.key : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"key","hash":{},"data":data}) : helper)))
    + ":\n    </div>\n    <div class=\"col-8\">\n        "
    + alias4(((helper = (helper = helpers.value || (depth0 != null ? depth0.value : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"value","hash":{},"data":data}) : helper)))
    + "\n    </div>\n</div>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options;

  stack1 = ((helper = (helper = helpers.details || (depth0 != null ? depth0.details : depth0)) != null ? helper : helpers.helperMissing),(options={"name":"details","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!helpers.details) { stack1 = helpers.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { return stack1; }
  else { return ''; }
},"useData":true});
templates['map_controls_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "        <a id='map_controls_hide_"
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "' class=\"map_controls_custom\" href=\"#\" title=\""
    + alias4(((helper = (helper = helpers.title || (depth0 != null ? depth0.title : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title","hash":{},"data":data}) : helper)))
    + "\">\n            <i id='map_controls_hide_"
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "_checkbox' class=\"fas fa-check-square\" style=\"width: 15px\"></i>\n            <i class=\"fas "
    + alias4(((helper = (helper = helpers.fa_class || (depth0 != null ? depth0.fa_class : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"fa_class","hash":{},"data":data}) : helper)))
    + "\" style=\"width: 15px\"></i>\n            <span class=\"sr-only\">"
    + alias4(((helper = (helper = helpers.title || (depth0 != null ? depth0.title : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title","hash":{},"data":data}) : helper)))
    + "</span>\n        </a>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, buffer = 
  "<div id=\"map_controls\" class=\"leaflet-draw-section\">\n    <div class=\"leaflet-draw-toolbar leaflet-bar leaflet-draw-toolbar-top\">\n";
  stack1 = ((helper = (helper = helpers.mapobjectgroups || (depth0 != null ? depth0.mapobjectgroups : depth0)) != null ? helper : helpers.helperMissing),(options={"name":"mapobjectgroups","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!helpers.mapobjectgroups) { stack1 = helpers.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "    </div>\n    <ul class=\"leaflet-draw-actions\"></ul>\n</div>";
},"useData":true});
templates['map_enemy_forces_template'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<div id=\"map_enemy_forces\" class=\"font-weight-bold\" data-toggle=\"tooltip\">\n    <div class=\"row\">\n        <div class=\"col\">\n            <span id=\"map_enemy_forces_numbers\">\n                <i id=\"map_enemy_forces_success\" class=\"fas fa-check-circle\" style=\"display: none;\"></i>\n                <i id=\"map_enemy_forces_warning\" class=\"fas fa-exclamation-triangle\" style=\"display: none;\"></i>\n                <span id=\"map_enemy_forces_count\">0</span>\n                /"
    + container.escapeExpression(((helper = (helper = helpers.enemy_forces_total || (depth0 != null ? depth0.enemy_forces_total : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"enemy_forces_total","hash":{},"data":data}) : helper)))
    + "\n                (<span id=\"map_enemy_forces_percent\">0</span>%)\n            </span>\n        </div>\n    </div>\n</div>";
},"useData":true});
templates['map_enemy_tooltip_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "    <div class=\"row\">\n        <div class=\"col-12 font-weight-bold\">"
    + alias4(((helper = (helper = helpers.admin_only_label || (depth0 != null ? depth0.admin_only_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"admin_only_label","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.id_label || (depth0 != null ? depth0.id_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.id || (depth0 != null ? depth0.id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.faction_label || (depth0 != null ? depth0.faction_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"faction_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.faction || (depth0 != null ? depth0.faction : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"faction","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.npc_id_label || (depth0 != null ? depth0.npc_id_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_id_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.npc_id || (depth0 != null ? depth0.npc_id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_id","hash":{},"data":data}) : helper)))
    + " ("
    + alias4(((helper = (helper = helpers.npc_id_type || (depth0 != null ? depth0.npc_id_type : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_id_type","hash":{},"data":data}) : helper)))
    + ")</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.attached_to_pack_label || (depth0 != null ? depth0.attached_to_pack_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"attached_to_pack_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.attached_to_pack || (depth0 != null ? depth0.attached_to_pack : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"attached_to_pack","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.is_mdt_label || (depth0 != null ? depth0.is_mdt_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"is_mdt_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.is_mdt || (depth0 != null ? depth0.is_mdt : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"is_mdt","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.mdt_id_label || (depth0 != null ? depth0.mdt_id_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mdt_id_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.mdt_id || (depth0 != null ? depth0.mdt_id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mdt_id","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.enemy_id_label || (depth0 != null ? depth0.enemy_id_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_id_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.enemy_id || (depth0 != null ? depth0.enemy_id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_id","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.visual_label || (depth0 != null ? depth0.visual_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"visual_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.visual || (depth0 != null ? depth0.visual : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"visual","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"map_enemy_tooltip leaflet-draw-section\">\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.npc_name_label || (depth0 != null ? depth0.npc_name_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_name_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.npc_name || (depth0 != null ? depth0.npc_name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_name","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.enemy_forces_label || (depth0 != null ? depth0.enemy_forces_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_forces_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.enemy_forces || (depth0 != null ? depth0.enemy_forces : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_forces","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.base_health_label || (depth0 != null ? depth0.base_health_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"base_health_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.base_health || (depth0 != null ? depth0.base_health : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"base_health","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = helpers.teeming_label || (depth0 != null ? depth0.teeming_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"teeming_label","hash":{},"data":data}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = helpers.teeming || (depth0 != null ? depth0.teeming : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"teeming","hash":{},"data":data}) : helper)))
    + "</div>\n    </div>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.is_user_admin : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</div>";
},"useData":true});
templates['map_enemy_visual_template'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div style=\"position: relative;\">\n    <div class=\"modifier modifier_0 "
    + alias4(((helper = (helper = helpers.modifier_0_classes || (depth0 != null ? depth0.modifier_0_classes : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"modifier_0_classes","hash":{},"data":data}) : helper)))
    + "\" style=\"display: none;\">\n        "
    + ((stack1 = ((helper = (helper = helpers.modifier_0_html || (depth0 != null ? depth0.modifier_0_html : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"modifier_0_html","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\n    </div>\n    <div class=\"modifier modifier_1 "
    + alias4(((helper = (helper = helpers.modifier_1_classes || (depth0 != null ? depth0.modifier_1_classes : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"modifier_1_classes","hash":{},"data":data}) : helper)))
    + "\" style=\"display: none;\">\n        "
    + ((stack1 = ((helper = (helper = helpers.modifier_1_html || (depth0 != null ? depth0.modifier_1_html : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"modifier_1_html","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\n    </div>\n    <div class=\"modifier modifier_2 "
    + alias4(((helper = (helper = helpers.modifier_2_classes || (depth0 != null ? depth0.modifier_2_classes : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"modifier_2_classes","hash":{},"data":data}) : helper)))
    + "\" style=\"display: none;\">\n        "
    + ((stack1 = ((helper = (helper = helpers.modifier_2_html || (depth0 != null ? depth0.modifier_2_html : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"modifier_2_html","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\n    </div>\n    <div class=\""
    + alias4(((helper = (helper = helpers.selection_classes_base || (depth0 != null ? depth0.selection_classes_base : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"selection_classes_base","hash":{},"data":data}) : helper)))
    + " "
    + alias4(((helper = (helper = helpers.selection_classes || (depth0 != null ? depth0.selection_classes : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"selection_classes","hash":{},"data":data}) : helper)))
    + "\">\n        <div class=\""
    + alias4(((helper = (helper = helpers.main_visual_classes || (depth0 != null ? depth0.main_visual_classes : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"main_visual_classes","hash":{},"data":data}) : helper)))
    + "\">\n            "
    + ((stack1 = ((helper = (helper = helpers.main_visual_html || (depth0 != null ? depth0.main_visual_html : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"main_visual_html","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\n        </div>\n    </div>\n</div>";
},"useData":true});
templates['map_killzone_edit_popup_template'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div id=\"map_killzone_edit_popup_inner\" class=\"popupCustom\">\n    <div class=\"form-group\">\n        <label for=\"map_killzone_edit_popup_color_"
    + alias4(((helper = (helper = helpers.id || (depth0 != null ? depth0.id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.color_label || (depth0 != null ? depth0.color_label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"color_label","hash":{},"data":data}) : helper)))
    + "</label>\n        <input type=\"color\" id=\"map_killzone_edit_popup_color_"
    + alias4(((helper = (helper = helpers.id || (depth0 != null ? depth0.id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data}) : helper)))
    + "\" class=\"form-control\"/>\n    </div>\n    <input type=\"submit\" id=\"map_killzone_edit_popup_submit_"
    + alias4(((helper = (helper = helpers.id || (depth0 != null ? depth0.id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data}) : helper)))
    + "\" class=\"btn btn-info\"/>\n</div>";
},"useData":true});
templates['routeattributes_row_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "    <div class=\"float-left\">\n        <div class=\"select_icon route_attribute-"
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + " mr-2\" style=\"height: 24px;\" data-toggle=\"tooltip\"\n             title=\""
    + alias4(((helper = (helper = helpers.description || (depth0 != null ? depth0.description : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"description","hash":{},"data":data}) : helper)))
    + "\">\n            &nbsp;\n        </div>\n    </div>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, buffer = 
  "<div class=\"row no-gutters\">\n";
  stack1 = ((helper = (helper = helpers.attributes || (depth0 != null ? depth0.attributes : depth0)) != null ? helper : helpers.helperMissing),(options={"name":"attributes","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!helpers.attributes) { stack1 = helpers.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>";
},"useData":true});
templates['thumbnailcarousel_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper;

  return "    <img src=\""
    + container.escapeExpression(((helper = (helper = helpers.src || (depth0 != null ? depth0.src : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"src","hash":{},"data":data}) : helper)))
    + "\"/>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, buffer = 
  "<div class=\"owl-carousel owl-theme\">\n";
  stack1 = ((helper = (helper = helpers.items || (depth0 != null ? depth0.items : depth0)) != null ? helper : helpers.helperMissing),(options={"name":"items","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!helpers.items) { stack1 = helpers.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>";
},"useData":true});
})();