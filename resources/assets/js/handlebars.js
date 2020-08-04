(function() {
  var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
templates['admin_users_table_row_actions'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"dropdown\">\r\n    <button class=\"btn btn-secondary dropdown-toggle\" type=\"button\" id=\"userActionsButton\" data-toggle=\"dropdown\"\r\n            aria-haspopup=\"true\" aria-expanded=\"false\">\r\n        "
    + alias4(((helper = (helper = lookupProperty(helpers,"actions_label") || (depth0 != null ? lookupProperty(depth0,"actions_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"actions_label","hash":{},"data":data,"loc":{"start":{"line":4,"column":8},"end":{"line":4,"column":25}}}) : helper)))
    + "\r\n    </button>\r\n    <div class=\"dropdown-menu\" aria-labelledby=\"userActionsButton\">\r\n        <a class=\"dropdown-item\" href=\"#\">\r\n            <form method=\"POST\" action=\"/admin/user/"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":8,"column":52},"end":{"line":8,"column":58}}}) : helper)))
    + "/makeadmin\" accept-charset=\"UTF-8\"\r\n                  autocomplete=\"off\">\r\n                <input name=\"_token\" type=\"hidden\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"csrf_token") || (depth0 != null ? lookupProperty(depth0,"csrf_token") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"csrf_token","hash":{},"data":data,"loc":{"start":{"line":10,"column":58},"end":{"line":10,"column":72}}}) : helper)))
    + "\">\r\n                <input class=\"btn btn-info\" name=\"submit\" type=\"submit\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"make_admin_label") || (depth0 != null ? lookupProperty(depth0,"make_admin_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"make_admin_label","hash":{},"data":data,"loc":{"start":{"line":11,"column":79},"end":{"line":11,"column":99}}}) : helper)))
    + "\">\r\n            </form>\r\n        </a>\r\n        <a class=\"dropdown-item\" href=\"#\">\r\n            <form method=\"POST\" action=\"/admin/user/"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":15,"column":52},"end":{"line":15,"column":58}}}) : helper)))
    + "/makeuser\" accept-charset=\"UTF-8\"\r\n                  autocomplete=\"off\">\r\n                <input name=\"_token\" type=\"hidden\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"csrf_token") || (depth0 != null ? lookupProperty(depth0,"csrf_token") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"csrf_token","hash":{},"data":data,"loc":{"start":{"line":17,"column":58},"end":{"line":17,"column":72}}}) : helper)))
    + "\">\r\n                <input class=\"btn btn-info ml-1\" name=\"submit\" type=\"submit\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"make_user_label") || (depth0 != null ? lookupProperty(depth0,"make_user_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"make_user_label","hash":{},"data":data,"loc":{"start":{"line":18,"column":84},"end":{"line":18,"column":103}}}) : helper)))
    + "\">\r\n            </form>\r\n        </a>\r\n        <a class=\"dropdown-item\" href=\"#\">\r\n            <form method=\"POST\" action=\"/admin/user/"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":22,"column":52},"end":{"line":22,"column":58}}}) : helper)))
    + "/delete\" accept-charset=\"UTF-8\"\r\n                  autocomplete=\"off\">\r\n                <input name=\"_method\" type=\"hidden\" value=\"DELETE\">\r\n                <input name=\"_token\" type=\"hidden\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"csrf_token") || (depth0 != null ? lookupProperty(depth0,"csrf_token") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"csrf_token","hash":{},"data":data,"loc":{"start":{"line":25,"column":58},"end":{"line":25,"column":72}}}) : helper)))
    + "\">\r\n                <input class=\"btn btn-danger ml-1\" name=\"submit\" type=\"submit\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"delete_user_label") || (depth0 != null ? lookupProperty(depth0,"delete_user_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"delete_user_label","hash":{},"data":data,"loc":{"start":{"line":26,"column":86},"end":{"line":26,"column":107}}}) : helper)))
    + "\">\r\n            </form>\r\n        </a>\r\n    </div>\r\n</div>";
},"useData":true});
templates['admin_users_table_row_patreon'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "        <option value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":4,"column":23},"end":{"line":4,"column":29}}}) : helper)))
    + "\"\r\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"selected") : depth0),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":5,"column":8},"end":{"line":7,"column":15}}})) != null ? stack1 : "")
    + ">"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":7,"column":16},"end":{"line":7,"column":24}}}) : helper)))
    + "</option>\r\n";
},"2":function(container,depth0,helpers,partials,data) {
    return "            selected=\"selected\"\r\n        ";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<select class=\"form-control selectpicker patreon_paid_tiers\" multiple=\"multiple\" data-selected-text-format=\"count > 1\"\r\n        data-count-selected-text=\"{0} paid tiers\" data-userid=\"8\" name=\"patreon_paid_tiers\" tabindex=\"null\">\r\n"
    + ((stack1 = lookupProperty(helpers,"each").call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? lookupProperty(depth0,"paidtiers") : depth0),{"name":"each","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":4},"end":{"line":8,"column":13}}})) != null ? stack1 : "")
    + "</select>";
},"useData":true});
templates['affixgroups_complex_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "    <div class=\"row affix_list_row no-gutters\" style=\"width: 200px\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"affixes") || (depth0 != null ? lookupProperty(depth0,"affixes") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"affixes","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":7,"column":8},"end":{"line":13,"column":20}}}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!lookupProperty(helpers,"affixes")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "    </div>\n";
},"2":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "        <div class=\"affix_row col-md-3\">\n            <div class=\"select_icon affix_icon_"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"class") || (depth0 != null ? lookupProperty(depth0,"class") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"class","hash":{},"data":data,"loc":{"start":{"line":9,"column":47},"end":{"line":9,"column":56}}}) : helper)))
    + " mr-2\" style=\"height: 24px;\">\n                &nbsp;\n            </div>\n        </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<span class=\"target_tooltip\" data-toggle=\"tooltip\" data-html=\"true\">\n    "
    + alias4(((helper = (helper = lookupProperty(helpers,"count") || (depth0 != null ? lookupProperty(depth0,"count") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"count","hash":{},"data":data,"loc":{"start":{"line":2,"column":4},"end":{"line":2,"column":13}}}) : helper)))
    + " "
    + alias4(((helper = (helper = lookupProperty(helpers,"selected_label") || (depth0 != null ? lookupProperty(depth0,"selected_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"selected_label","hash":{},"data":data,"loc":{"start":{"line":2,"column":14},"end":{"line":2,"column":32}}}) : helper)))
    + "\n</span>\n<div class=\"affix_list_row_container\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"affixgroups") || (depth0 != null ? lookupProperty(depth0,"affixgroups") : depth0)) != null ? helper : alias2),(options={"name":"affixgroups","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":5,"column":4},"end":{"line":15,"column":20}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"affixgroups")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>";
},"useData":true});
templates['affixgroups_single_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div class=\"affix_list_row row no-gutters\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"affixes") || (depth0 != null ? lookupProperty(depth0,"affixes") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"affixes","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":4},"end":{"line":10,"column":16}}}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!lookupProperty(helpers,"affixes")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>\n";
},"2":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <div class=\"affix_row float-left\">\n        <div class=\"select_icon affix_icon_"
    + alias4(((helper = (helper = lookupProperty(helpers,"class") || (depth0 != null ? lookupProperty(depth0,"class") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"class","hash":{},"data":data,"loc":{"start":{"line":5,"column":43},"end":{"line":5,"column":52}}}) : helper)))
    + " mr-2\" style=\"height: 24px;\" data-toggle=\"tooltip\"\n             title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":6,"column":20},"end":{"line":6,"column":28}}}) : helper)))
    + "\">\n            &nbsp;\n        </div>\n    </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  stack1 = ((helper = (helper = lookupProperty(helpers,"affixgroups") || (depth0 != null ? lookupProperty(depth0,"affixgroups") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"affixgroups","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":12,"column":16}}}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!lookupProperty(helpers,"affixgroups")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { return stack1; }
  else { return ''; }
},"useData":true});
templates['affixgroup_select_option_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "        <div class=\"col affix_row\">\n            <div class=\"select_icon affix_icon_"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"class") || (depth0 != null ? lookupProperty(depth0,"class") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"class","hash":{},"data":data,"loc":{"start":{"line":4,"column":47},"end":{"line":4,"column":56}}}) : helper)))
    + "\" style=\"height: 24px;\"> </div>\n        </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div class=\"row affix_row_container no-gutters\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"affixes") || (depth0 != null ? lookupProperty(depth0,"affixes") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"affixes","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":2,"column":4},"end":{"line":6,"column":16}}}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!lookupProperty(helpers,"affixes")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>";
},"useData":true});
templates['app_fixed_message_small_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"text-center m-1\">\n    <span class=\"alert alert-"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"type") || (depth0 != null ? lookupProperty(depth0,"type") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"type","hash":{},"data":data,"loc":{"start":{"line":2,"column":29},"end":{"line":2,"column":37}}}) : helper)))
    + " mb-0 alert_fixed_footer\">\n        "
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"message") || (depth0 != null ? lookupProperty(depth0,"message") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"message","hash":{},"data":data,"loc":{"start":{"line":3,"column":8},"end":{"line":3,"column":21}}}) : helper))) != null ? stack1 : "")
    + "\n    </span>\n</div>";
},"useData":true});
templates['app_fixed_message_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"alert alert-"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"type") || (depth0 != null ? lookupProperty(depth0,"type") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"type","hash":{},"data":data,"loc":{"start":{"line":1,"column":24},"end":{"line":1,"column":32}}}) : helper)))
    + " mb-0 text-center m-1 alert_fixed_footer\">\n    "
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"message") || (depth0 != null ? lookupProperty(depth0,"message") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"message","hash":{},"data":data,"loc":{"start":{"line":2,"column":4},"end":{"line":2,"column":17}}}) : helper))) != null ? stack1 : "")
    + "\n</div>";
},"useData":true});
templates['biglistfeatures_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <div class=\"row no-gutters mt-1 d-none d-md-flex\">\n        <div class=\"col-xl-5\">"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"attributes_label") || (depth0 != null ? lookupProperty(depth0,"attributes_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"attributes_label","hash":{},"data":data,"loc":{"start":{"line":10,"column":30},"end":{"line":10,"column":50}}}) : helper)))
    + ":</div>\n        <div class=\"col-xl-7\">\n            "
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"attributes") || (depth0 != null ? lookupProperty(depth0,"attributes") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"attributes","hash":{},"data":data,"loc":{"start":{"line":12,"column":12},"end":{"line":12,"column":28}}}) : helper))) != null ? stack1 : "")
    + "\n        </div>\n    </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"row no-gutters mt-1\">\n    <div class=\"col-xl-5 d-none d-md-flex\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"affixes_label") || (depth0 != null ? lookupProperty(depth0,"affixes_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"affixes_label","hash":{},"data":data,"loc":{"start":{"line":2,"column":43},"end":{"line":2,"column":60}}}) : helper)))
    + ":</div>\n    <div class=\"col-xl-7\">\n        "
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"affixes") || (depth0 != null ? lookupProperty(depth0,"affixes") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"affixes","hash":{},"data":data,"loc":{"start":{"line":4,"column":8},"end":{"line":4,"column":21}}}) : helper))) != null ? stack1 : "")
    + "\n    </div>\n</div>\n\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"showAttributes") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":8,"column":0},"end":{"line":15,"column":7}}})) != null ? stack1 : "")
    + "<div class=\"row no-gutters mt-1 d-none d-lg-flex\">\n    <div class=\"col-xl-5\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"setup_label") || (depth0 != null ? lookupProperty(depth0,"setup_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"setup_label","hash":{},"data":data,"loc":{"start":{"line":17,"column":26},"end":{"line":17,"column":41}}}) : helper)))
    + ":</div>\n    <div class=\"col-xl-7\">\n        "
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"setup") || (depth0 != null ? lookupProperty(depth0,"setup") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"setup","hash":{},"data":data,"loc":{"start":{"line":19,"column":8},"end":{"line":19,"column":19}}}) : helper))) != null ? stack1 : "")
    + "\n    </div>\n</div>";
},"useData":true});
templates['composition_icon_option_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"row no-gutters\">\n    <div class=\"col-auto select_icon class_icon "
    + alias4(((helper = (helper = lookupProperty(helpers,"css_class") || (depth0 != null ? lookupProperty(depth0,"css_class") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"css_class","hash":{},"data":data,"loc":{"start":{"line":2,"column":48},"end":{"line":2,"column":61}}}) : helper)))
    + "\" style=\"height: 24px;\">\n    </div>\n    <div class=\"col pl-1\">\n        "
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":5,"column":8},"end":{"line":5,"column":16}}}) : helper)))
    + "\n    </div>\n</div>";
},"useData":true});
templates['dungeonroute_table_profile_actions_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "            <a class=\"dropdown-item dungeonroute-unpublish text-warning cursor-pointer\" data-publickey=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"public_key") || (depth0 != null ? lookupProperty(depth0,"public_key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"public_key","hash":{},"data":data,"loc":{"start":{"line":8,"column":104},"end":{"line":8,"column":118}}}) : helper)))
    + "\">\n                <i class=\"fas fa-plane-arrival\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"unpublish_label") || (depth0 != null ? lookupProperty(depth0,"unpublish_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"unpublish_label","hash":{},"data":data,"loc":{"start":{"line":9,"column":53},"end":{"line":9,"column":72}}}) : helper)))
    + "\n            </a>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "            <a class=\"dropdown-item dungeonroute-publish text-success cursor-pointer\" data-publickey=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"public_key") || (depth0 != null ? lookupProperty(depth0,"public_key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"public_key","hash":{},"data":data,"loc":{"start":{"line":12,"column":102},"end":{"line":12,"column":116}}}) : helper)))
    + "\">\n                <i class=\"fas fa-plane-departure\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"publish_label") || (depth0 != null ? lookupProperty(depth0,"publish_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publish_label","hash":{},"data":data,"loc":{"start":{"line":13,"column":55},"end":{"line":13,"column":72}}}) : helper)))
    + "\n            </a>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"dropdown\">\n    <button class=\"btn btn-secondary dropdown-toggle\" type=\"button\" id=\"dropdownMenuButton\" data-toggle=\"dropdown\"\n            aria-haspopup=\"true\" aria-expanded=\"false\">\n        "
    + alias4(((helper = (helper = lookupProperty(helpers,"actions_label") || (depth0 != null ? lookupProperty(depth0,"actions_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"actions_label","hash":{},"data":data,"loc":{"start":{"line":4,"column":8},"end":{"line":4,"column":25}}}) : helper)))
    + "\n    </button>\n    <div class=\"dropdown-menu\" aria-labelledby=\"dropdownMenuButton\">\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"published") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(3, data, 0),"data":data,"loc":{"start":{"line":7,"column":8},"end":{"line":15,"column":15}}})) != null ? stack1 : "")
    + "        <div class=\"dropdown-divider\"></div>\n        <a class=\"dropdown-item dungeonroute-clone cursor-pointer\" data-publickey=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"public_key") || (depth0 != null ? lookupProperty(depth0,"public_key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"public_key","hash":{},"data":data,"loc":{"start":{"line":17,"column":83},"end":{"line":17,"column":97}}}) : helper)))
    + "\">\n            <i class=\"fas fa-clone\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"clone_label") || (depth0 != null ? lookupProperty(depth0,"clone_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"clone_label","hash":{},"data":data,"loc":{"start":{"line":18,"column":41},"end":{"line":18,"column":56}}}) : helper)))
    + "\n        </a>\n        <a class=\"dropdown-item dungeonroute-clone-to-team cursor-pointer\" data-publickey=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"public_key") || (depth0 != null ? lookupProperty(depth0,"public_key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"public_key","hash":{},"data":data,"loc":{"start":{"line":20,"column":91},"end":{"line":20,"column":105}}}) : helper)))
    + "\">\n            <i class=\"fas fa-clone\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"clone_to_team_label") || (depth0 != null ? lookupProperty(depth0,"clone_to_team_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"clone_to_team_label","hash":{},"data":data,"loc":{"start":{"line":21,"column":41},"end":{"line":21,"column":64}}}) : helper)))
    + "\n        </a>\n        <div class=\"dropdown-divider\"></div>\n        <a class=\"dropdown-item dungeonroute-delete text-danger cursor-pointer\" data-publickey=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"public_key") || (depth0 != null ? lookupProperty(depth0,"public_key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"public_key","hash":{},"data":data,"loc":{"start":{"line":24,"column":96},"end":{"line":24,"column":110}}}) : helper)))
    + "\">\n            <i class=\"fas fa-trash\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"delete_label") || (depth0 != null ? lookupProperty(depth0,"delete_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"delete_label","hash":{},"data":data,"loc":{"start":{"line":25,"column":41},"end":{"line":25,"column":57}}}) : helper)))
    + "\n        </a>\n    </div>\n</div>";
},"useData":true});
templates['dungeonroute_table_profile_clone_to_team_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "        <option>"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"name","hash":{},"data":data,"loc":{"start":{"line":3,"column":16},"end":{"line":3,"column":24}}}) : helper)))
    + "</option>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<select id=\"clone-to-teams-"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"publicKey") || (depth0 != null ? lookupProperty(depth0,"publicKey") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"publicKey","hash":{},"data":data,"loc":{"start":{"line":1,"column":27},"end":{"line":1,"column":40}}}) : helper)))
    + "\" class=\"selectpicker\">\n"
    + ((stack1 = lookupProperty(helpers,"each").call(alias1,(depth0 != null ? lookupProperty(depth0,"teams") : depth0),{"name":"each","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":2,"column":4},"end":{"line":4,"column":13}}})) != null ? stack1 : "")
    + "</select>";
},"useData":true});
templates['dungeonroute_table_profile_enemy_forces_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <span class=\"text-success\">\n        <i class=\"fas fa-check-circle\"></i>\n        "
    + alias4(((helper = (helper = lookupProperty(helpers,"enemy_forces") || (depth0 != null ? lookupProperty(depth0,"enemy_forces") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_forces","hash":{},"data":data,"loc":{"start":{"line":4,"column":8},"end":{"line":4,"column":24}}}) : helper)))
    + "/"
    + alias4(((helper = (helper = lookupProperty(helpers,"enemy_forces_required") || (depth0 != null ? lookupProperty(depth0,"enemy_forces_required") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_forces_required","hash":{},"data":data,"loc":{"start":{"line":4,"column":25},"end":{"line":4,"column":50}}}) : helper)))
    + "\n    </span>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <span class=\"text-warning\">\n        <i class=\"fas fa-exclamation-triangle\"></i>\n        "
    + alias4(((helper = (helper = lookupProperty(helpers,"enemy_forces") || (depth0 != null ? lookupProperty(depth0,"enemy_forces") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_forces","hash":{},"data":data,"loc":{"start":{"line":9,"column":8},"end":{"line":9,"column":24}}}) : helper)))
    + "/"
    + alias4(((helper = (helper = lookupProperty(helpers,"enemy_forces_required") || (depth0 != null ? lookupProperty(depth0,"enemy_forces_required") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_forces_required","hash":{},"data":data,"loc":{"start":{"line":9,"column":25},"end":{"line":9,"column":50}}}) : helper)))
    + "\n    </span>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? lookupProperty(depth0,"enough") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(3, data, 0),"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":11,"column":7}}})) != null ? stack1 : "");
},"useData":true});
templates['group_setup_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <div class=\"col-auto select_icon mr-2 class_icon "
    + alias4(((helper = (helper = lookupProperty(helpers,"css_class") || (depth0 != null ? lookupProperty(depth0,"css_class") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"css_class","hash":{},"data":data,"loc":{"start":{"line":6,"column":53},"end":{"line":6,"column":66}}}) : helper)))
    + "\" style=\"height: 24px;\" data-toggle=\"tooltip\"\n         title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title") || (depth0 != null ? lookupProperty(depth0,"title") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title","hash":{},"data":data,"loc":{"start":{"line":7,"column":16},"end":{"line":7,"column":25}}}) : helper)))
    + "\">\n    </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div class=\"row no-gutters\">\n    <div class=\"col-auto select_icon mr-2 "
    + alias4(((helper = (helper = lookupProperty(helpers,"css_class") || (depth0 != null ? lookupProperty(depth0,"css_class") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"css_class","hash":{},"data":data,"loc":{"start":{"line":2,"column":42},"end":{"line":2,"column":55}}}) : helper)))
    + "\" style=\"height: 24px;\" data-toggle=\"tooltip\"\n         title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"faction_title") || (depth0 != null ? lookupProperty(depth0,"faction_title") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"faction_title","hash":{},"data":data,"loc":{"start":{"line":3,"column":16},"end":{"line":3,"column":33}}}) : helper)))
    + "\">\n    </div>\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"classes") || (depth0 != null ? lookupProperty(depth0,"classes") : depth0)) != null ? helper : alias2),(options={"name":"classes","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":5,"column":4},"end":{"line":9,"column":16}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"classes")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>";
},"useData":true});
templates['import_string_details_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <div class=\"row no-gutters\">\n        <div class=\"col-4\">\n            "
    + alias4(((helper = (helper = lookupProperty(helpers,"key") || (depth0 != null ? lookupProperty(depth0,"key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"key","hash":{},"data":data,"loc":{"start":{"line":4,"column":12},"end":{"line":4,"column":19}}}) : helper)))
    + ":\n        </div>\n        <div class=\"col-8\">\n            "
    + alias4(((helper = (helper = lookupProperty(helpers,"value") || (depth0 != null ? lookupProperty(depth0,"value") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"value","hash":{},"data":data,"loc":{"start":{"line":7,"column":12},"end":{"line":7,"column":21}}}) : helper)))
    + "\n        </div>\n    </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  stack1 = ((helper = (helper = lookupProperty(helpers,"details") || (depth0 != null ? lookupProperty(depth0,"details") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"details","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":10,"column":12}}}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!lookupProperty(helpers,"details")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { return stack1; }
  else { return ''; }
},"useData":true});
templates['import_string_warnings_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <div class=\"row no-gutters\">\n        <div class=\"col-4\">\n            "
    + alias4(((helper = (helper = lookupProperty(helpers,"category") || (depth0 != null ? lookupProperty(depth0,"category") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"category","hash":{},"data":data,"loc":{"start":{"line":17,"column":12},"end":{"line":17,"column":24}}}) : helper)))
    + ":\n        </div>\n        <div class=\"col-8\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"details") || (depth0 != null ? lookupProperty(depth0,"details") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"details","hash":{},"data":data,"loc":{"start":{"line":19,"column":34},"end":{"line":19,"column":45}}}) : helper)))
    + "\" data-toggle=\"tooltip\">\n            "
    + alias4(((helper = (helper = lookupProperty(helpers,"message") || (depth0 != null ? lookupProperty(depth0,"message") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"message","hash":{},"data":data,"loc":{"start":{"line":20,"column":12},"end":{"line":20,"column":23}}}) : helper)))
    + "\n        </div>\n    </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div class=\"alert alert-warning\" role=\"alert\">\n    <i class=\"fa fa-exclamation-triangle\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"warnings_label") || (depth0 != null ? lookupProperty(depth0,"warnings_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"warnings_label","hash":{},"data":data,"loc":{"start":{"line":2,"column":47},"end":{"line":2,"column":65}}}) : helper)))
    + "\n</div>\n\n<div class=\"row no-gutters\">\n    <div class=\"col-4 font-weight-bold\">\n        "
    + alias4(((helper = (helper = lookupProperty(helpers,"category_label") || (depth0 != null ? lookupProperty(depth0,"category_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"category_label","hash":{},"data":data,"loc":{"start":{"line":7,"column":8},"end":{"line":7,"column":26}}}) : helper)))
    + "\n    </div>\n    <div class=\"col-8 font-weight-bold\">\n        "
    + alias4(((helper = (helper = lookupProperty(helpers,"message_label") || (depth0 != null ? lookupProperty(depth0,"message_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"message_label","hash":{},"data":data,"loc":{"start":{"line":10,"column":8},"end":{"line":10,"column":25}}}) : helper)))
    + "\n    </div>\n</div>\n\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"warnings") || (depth0 != null ? lookupProperty(depth0,"warnings") : depth0)) != null ? helper : alias2),(options={"name":"warnings","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":14,"column":0},"end":{"line":23,"column":13}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"warnings")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer;
},"useData":true});
templates['map_admin_panel_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    return "<div class=\"leaflet-control\">\n    <div id=\"admin_panel_mouse_coordinates\">\n\n    </div>\n</div>";
},"useData":true});
templates['map_controls_route_echo_member_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    data-toggle=\"tooltip\" title=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"member_self_title_label") || (depth0 != null ? lookupProperty(depth0,"member_self_title_label") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"member_self_title_label","hash":{},"data":data,"loc":{"start":{"line":3,"column":33},"end":{"line":3,"column":60}}}) : helper)))
    + "\"\n";
},"3":function(container,depth0,helpers,partials,data) {
    return "        <i class=\"fas fa-portrait\"></i>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<li class=\"list-group-item p-2 m-1 echo_user_"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":1,"column":45},"end":{"line":1,"column":53}}}) : helper)))
    + " user_color_"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":1,"column":65},"end":{"line":1,"column":73}}}) : helper)))
    + "\"\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"self") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":2,"column":4},"end":{"line":4,"column":11}}})) != null ? stack1 : "")
    + ">\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"self") : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":6,"column":4},"end":{"line":8,"column":11}}})) != null ? stack1 : "")
    + "    "
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":9,"column":4},"end":{"line":9,"column":12}}}) : helper)))
    + "\n</li>";
},"useData":true});
templates['map_controls_route_echo_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"row\">\n    <div id=\"edit_route_echo_status_container\" class=\"col-4\">\n        <div class=\"connecting\">\n            <ul class=\"list-group list-group-horizontal\">\n                <li class=\"list-group-item pl-1 pr-1\" data-toggle=\"tooltip\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"connecting_title_label") || (depth0 != null ? lookupProperty(depth0,"connecting_title_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"connecting_title_label","hash":{},"data":data,"loc":{"start":{"line":5,"column":83},"end":{"line":5,"column":109}}}) : helper)))
    + "\">\n                    <img src=\"/images/echo/connecting.gif\" style=\"max-height: 25px;\" alt=\"\"/>\n                </li>\n                <li class=\"list-group-item pl-1\" data-toggle=\"tooltip\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"connecting_title_label") || (depth0 != null ? lookupProperty(depth0,"connecting_title_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"connecting_title_label","hash":{},"data":data,"loc":{"start":{"line":8,"column":78},"end":{"line":8,"column":104}}}) : helper)))
    + "\">\n                    <span>\n                        "
    + alias4(((helper = (helper = lookupProperty(helpers,"echo_connecting_label") || (depth0 != null ? lookupProperty(depth0,"echo_connecting_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"echo_connecting_label","hash":{},"data":data,"loc":{"start":{"line":10,"column":24},"end":{"line":10,"column":49}}}) : helper)))
    + "\n                    </span>\n                </li>\n            </ul>\n        </div>\n        <div class=\"connected\" style=\"display: none;\">\n            <ul class=\"list-group list-group-horizontal\">\n                <li class=\"list-group-item pl-1 pr-1\" data-toggle=\"tooltip\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"connected_title_label") || (depth0 != null ? lookupProperty(depth0,"connected_title_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"connected_title_label","hash":{},"data":data,"loc":{"start":{"line":17,"column":83},"end":{"line":17,"column":108}}}) : helper)))
    + "\">\n                    <img src=\"/images/echo/connected.gif\" style=\"max-height: 25px;\" alt=\"\"/>\n                </li>\n                <li class=\"list-group-item pl-1\" data-toggle=\"tooltip\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"connected_title_label") || (depth0 != null ? lookupProperty(depth0,"connected_title_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"connected_title_label","hash":{},"data":data,"loc":{"start":{"line":20,"column":78},"end":{"line":20,"column":103}}}) : helper)))
    + "\">\n                    <span>\n                        "
    + alias4(((helper = (helper = lookupProperty(helpers,"echo_connected_label") || (depth0 != null ? lookupProperty(depth0,"echo_connected_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"echo_connected_label","hash":{},"data":data,"loc":{"start":{"line":22,"column":24},"end":{"line":22,"column":48}}}) : helper)))
    + "\n                    </span>\n                </li>\n            </ul>\n        </div>\n    </div>\n    <div class=\"col-8 d-flex justify-content-end\">\n        <ul id=\"edit_route_echo_members_container\" class=\"list-group list-group-horizontal\">\n            <li class=\"list-group-item\" data-toggle=\"tooltip\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"members_title_label") || (depth0 != null ? lookupProperty(depth0,"members_title_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"members_title_label","hash":{},"data":data,"loc":{"start":{"line":30,"column":69},"end":{"line":30,"column":92}}}) : helper)))
    + "\">\n                <i class=\"fa fa-users\"></i>\n            </li>\n        </ul>\n    </div>\n</div>";
},"useData":true});
templates['map_controls_route_edit_button_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"row no-gutters text-center h-100\">\n    <div class=\"d-md-block d-none button_hotkey_label\">\n        "
    + alias4(((helper = (helper = lookupProperty(helpers,"hotkey") || (depth0 != null ? lookupProperty(depth0,"hotkey") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"hotkey","hash":{},"data":data,"loc":{"start":{"line":3,"column":8},"end":{"line":3,"column":18}}}) : helper)))
    + "\n    </div>\n    <div class=\"col p-1 ml-1 mr-1\" style=\"display: flex; justify-content: center; align-items: center;\">\n        <div class=\"text-center\">\n            <i class=\"fas "
    + alias4(((helper = (helper = lookupProperty(helpers,"fa_class") || (depth0 != null ? lookupProperty(depth0,"fa_class") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"fa_class","hash":{},"data":data,"loc":{"start":{"line":7,"column":26},"end":{"line":7,"column":40}}}) : helper)))
    + " \"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"text") || (depth0 != null ? lookupProperty(depth0,"text") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"text","hash":{},"data":data,"loc":{"start":{"line":7,"column":48},"end":{"line":7,"column":58}}}) : helper)))
    + "\n        </div>\n    </div>\n</div>";
},"useData":true});
templates['map_controls_route_edit_freedraw_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    return "                <option value=\"1\">1</option>\n                <option value=\"2\">2</option>\n                <option value=\"3\">3</option>\n                <option value=\"4\">4</option>\n                <option value=\"5\">5</option>\n                <option value=\"6\">6</option>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"col draw_element\">\n    <div class=\"ml-1 mr-1 h-100\">\n        <button id=\"edit_route_freedraw_options_color\"\n             class=\"btn w-100\" style=\"background-color: "
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"color") || (depth0 != null ? lookupProperty(depth0,"color") : depth0)) != null ? helper : alias2),(typeof helper === "function" ? helper.call(alias1,{"name":"color","hash":{},"data":data,"loc":{"start":{"line":4,"column":56},"end":{"line":4,"column":65}}}) : helper)))
    + "\">\n\n        </button>\n    </div>\n</div>\n\n<div class=\"col draw_element\">\n    <div class=\"ml-1 mr-1\">\n        <select id=\"edit_route_freedraw_options_weight\" class=\"form-control selectpicker\"\n                name=\"edit_route_freedraw_options_weight\">\n"
    + ((stack1 = (lookupProperty(helpers,"select")||(depth0 && lookupProperty(depth0,"select"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"weight") : depth0),{"name":"select","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":14,"column":12},"end":{"line":21,"column":23}}})) != null ? stack1 : "")
    + "        </select>\n    </div>\n</div>";
},"useData":true});
templates['map_controls_route_edit_settings_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    return "<div class=\"col draw_element\">\n    <div class=\"dropup\">\n      <button type=\"button\" class=\"btn btn-secondary dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">\n          <i class=\"fa fa-eye\"></i>\n      </button>\n      <div class=\"dropdown-menu\">\n          <a class=\"dropdown-item\" href=\"#\">\n              test\n          </a>\n          <a class=\"dropdown-item\" href=\"#\">\n              test2\n          </a>\n      </div>\n    </div>\n</div>";
},"useData":true});
templates['map_controls_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "            <a id='map_controls_hide_"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":9,"column":37},"end":{"line":9,"column":45}}}) : helper)))
    + "' class=\"map_controls_custom\" href=\"#\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title") || (depth0 != null ? lookupProperty(depth0,"title") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title","hash":{},"data":data,"loc":{"start":{"line":9,"column":91},"end":{"line":9,"column":100}}}) : helper)))
    + "\">\n                <i id='map_controls_hide_"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":10,"column":41},"end":{"line":10,"column":49}}}) : helper)))
    + "_checkbox' class=\"fas fa-check-square\" style=\"width: 15px\"></i>\n                <i class=\"fas "
    + alias4(((helper = (helper = lookupProperty(helpers,"fa_class") || (depth0 != null ? lookupProperty(depth0,"fa_class") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"fa_class","hash":{},"data":data,"loc":{"start":{"line":11,"column":30},"end":{"line":11,"column":42}}}) : helper)))
    + "\" style=\"width: 15px\"></i>\n                <span class=\"sr-only\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"title") || (depth0 != null ? lookupProperty(depth0,"title") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title","hash":{},"data":data,"loc":{"start":{"line":12,"column":38},"end":{"line":12,"column":47}}}) : helper)))
    + "</span>\n            </a>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div id=\"map_controls\" class=\"leaflet-draw-section\">\n    <div class=\"leaflet-draw-toolbar leaflet-bar leaflet-draw-toolbar-top\">\n        <a id='map_controls_hide_"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":3,"column":33},"end":{"line":3,"column":41}}}) : helper)))
    + "' class=\"map_controls_custom\" href=\"#\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title") || (depth0 != null ? lookupProperty(depth0,"title") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title","hash":{},"data":data,"loc":{"start":{"line":3,"column":87},"end":{"line":3,"column":96}}}) : helper)))
    + "\">\n            <i id='map_controls_hide_"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":4,"column":37},"end":{"line":4,"column":45}}}) : helper)))
    + "_checkbox' class=\"fas fa-check-square\" style=\"width: 15px\"></i>\n            <i class=\"fas "
    + alias4(((helper = (helper = lookupProperty(helpers,"fa_class") || (depth0 != null ? lookupProperty(depth0,"fa_class") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"fa_class","hash":{},"data":data,"loc":{"start":{"line":5,"column":26},"end":{"line":5,"column":38}}}) : helper)))
    + "\" style=\"width: 15px\"></i>\n            <span class=\"sr-only\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"title") || (depth0 != null ? lookupProperty(depth0,"title") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title","hash":{},"data":data,"loc":{"start":{"line":6,"column":34},"end":{"line":6,"column":43}}}) : helper)))
    + "</span>\n        </a>\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"mapobjectgroups") || (depth0 != null ? lookupProperty(depth0,"mapobjectgroups") : depth0)) != null ? helper : alias2),(options={"name":"mapobjectgroups","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":8,"column":8},"end":{"line":14,"column":28}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"mapobjectgroups")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "    </div>\n    <ul class=\"leaflet-draw-actions\"></ul>\n</div>";
},"useData":true});
templates['map_dungeon_floor_switch_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "            <option value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":8,"column":27},"end":{"line":8,"column":33}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":8,"column":35},"end":{"line":8,"column":43}}}) : helper)))
    + "</option>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div class=\"popupCustom\">\n    <div class=\"form-group\">\n        <label for=\"dungeon_floor_switch_edit_popup_target_floor\">"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"connected_floor_label") || (depth0 != null ? lookupProperty(depth0,"connected_floor_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"connected_floor_label","hash":{},"data":data,"loc":{"start":{"line":3,"column":66},"end":{"line":3,"column":91}}}) : helper)))
    + "</label>\n        <select id=\"dungeon_floor_switch_edit_popup_target_floor\"\n                name=\"dungeon_floor_switch_edit_popup_target_floor\"\n                class=\"selectpicker dungeon_floor_switch_edit_popup_target_floor\" data-width=\"300px\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"floors") || (depth0 != null ? lookupProperty(depth0,"floors") : depth0)) != null ? helper : alias2),(options={"name":"floors","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":7,"column":12},"end":{"line":9,"column":23}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"floors")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "        </select>\n    </div>\n    <input type=\"submit\" id=\"dungeon_floor_switch_edit_popup_submit\" class=\"btn btn-info\"/>\n</div>";
},"useData":true});
templates['map_enemy_forces_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div id=\"map_enemy_forces\" class=\"col-6 m-auto text-center font-weight-bold text-\" data-toggle=\"tooltip\">\n    <span id=\"map_enemy_forces_numbers\">\n        <i id=\"map_enemy_forces_success\" class=\"fas fa-check-circle\" style=\"display: none;\"></i>\n        <i id=\"map_enemy_forces_warning\" class=\"fas fa-exclamation-triangle\" style=\"display: none;\"></i>\n        <span id=\"map_enemy_forces_count\">0</span>/"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"enemy_forces_total") || (depth0 != null ? lookupProperty(depth0,"enemy_forces_total") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"enemy_forces_total","hash":{},"data":data,"loc":{"start":{"line":5,"column":51},"end":{"line":5,"column":73}}}) : helper)))
    + " (<span\n            id=\"map_enemy_forces_percent\">0</span>%)\n    </span>\n</div>";
},"useData":true});
templates['map_enemy_forces_template_view'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div id=\"map_enemy_forces\" class=\"font-weight-bold\" data-toggle=\"tooltip\">\n    <span id=\"map_enemy_forces_numbers\">\n        <i id=\"map_enemy_forces_success\" class=\"fas fa-check-circle\" style=\"display: none;\"></i>\n        <i id=\"map_enemy_forces_warning\" class=\"fas fa-exclamation-triangle\" style=\"display: none;\"></i>\n        <span id=\"map_enemy_forces_count\">0</span>/<span id=\"map_enemy_forces_count_total\">"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"enemy_forces_total") || (depth0 != null ? lookupProperty(depth0,"enemy_forces_total") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"enemy_forces_total","hash":{},"data":data,"loc":{"start":{"line":5,"column":91},"end":{"line":5,"column":113}}}) : helper)))
    + "</span> (<span\n            id=\"map_enemy_forces_percent\">0</span>%)\n    </span>\n</div>";
},"useData":true});
templates['map_enemy_pack_beguiling_row_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = "";

  stack1 = ((helper = (helper = lookupProperty(helpers,"npcs") || (depth0 != null ? lookupProperty(depth0,"npcs") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"npcs","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":13,"column":16},"end":{"line":15,"column":25}}}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!lookupProperty(helpers,"npcs")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer;
},"2":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "                    <option value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":14,"column":35},"end":{"line":14,"column":41}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":14,"column":43},"end":{"line":14,"column":51}}}) : helper)))
    + " ("
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":14,"column":53},"end":{"line":14,"column":59}}}) : helper)))
    + ")</option>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div id=\"enemy_pack_edit_popup_beguiling_row_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":1,"column":45},"end":{"line":1,"column":51}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"index") || (depth0 != null ? lookupProperty(depth0,"index") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"index","hash":{},"data":data,"loc":{"start":{"line":1,"column":52},"end":{"line":1,"column":61}}}) : helper)))
    + "\" class=\"row no-gutters mt-1\">\n    <div class=\"col-4\">\n        <input type=\"text\" class=\"form-control\"\n               id=\"enemy_pack_edit_popup_beguiling_preset_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":4,"column":58},"end":{"line":4,"column":64}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"index") || (depth0 != null ? lookupProperty(depth0,"index") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"index","hash":{},"data":data,"loc":{"start":{"line":4,"column":65},"end":{"line":4,"column":74}}}) : helper)))
    + "\"\n               name=\"enemy_pack_edit_popup_beguiling_preset_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":5,"column":60},"end":{"line":5,"column":66}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"index") || (depth0 != null ? lookupProperty(depth0,"index") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"index","hash":{},"data":data,"loc":{"start":{"line":5,"column":67},"end":{"line":5,"column":76}}}) : helper)))
    + "\"\n               value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"preset") || (depth0 != null ? lookupProperty(depth0,"preset") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"preset","hash":{},"data":data,"loc":{"start":{"line":6,"column":22},"end":{"line":6,"column":32}}}) : helper)))
    + "\"/>\n    </div>\n    <div class=\"col-7\">\n        <select data-live-search=\"true\" id=\"enemy_pack_edit_popup_beguiling_npc_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":9,"column":80},"end":{"line":9,"column":86}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"index") || (depth0 != null ? lookupProperty(depth0,"index") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"index","hash":{},"data":data,"loc":{"start":{"line":9,"column":87},"end":{"line":9,"column":96}}}) : helper)))
    + "\"\n                name=\"enemy_pack_edit_popup_beguiling_npc_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":10,"column":58},"end":{"line":10,"column":64}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"index") || (depth0 != null ? lookupProperty(depth0,"index") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"index","hash":{},"data":data,"loc":{"start":{"line":10,"column":65},"end":{"line":10,"column":74}}}) : helper)))
    + "\"\n                class=\"selectpicker popup_select\" data-width=\"230px\">\n"
    + ((stack1 = (lookupProperty(helpers,"select")||(depth0 && lookupProperty(depth0,"select"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"npc_id") : depth0),{"name":"select","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":12,"column":12},"end":{"line":16,"column":23}}})) != null ? stack1 : "")
    + "        </select>\n    </div>\n    <div class=\"col-1\">\n        <button id=\"enemy_pack_edit_popup_beguiling_delete_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":20,"column":59},"end":{"line":20,"column":65}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"index") || (depth0 != null ? lookupProperty(depth0,"index") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"index","hash":{},"data":data,"loc":{"start":{"line":20,"column":66},"end":{"line":20,"column":75}}}) : helper)))
    + "\" class=\"btn btn-danger\">\n            <i class=\"fas fa-trash\"></i>\n        </button>\n    </div>\n</div>";
},"useData":true});
templates['map_enemy_pack_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "                <option value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"key") || (depth0 != null ? lookupProperty(depth0,"key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"key","hash":{},"data":data,"loc":{"start":{"line":10,"column":31},"end":{"line":10,"column":38}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"description") || (depth0 != null ? lookupProperty(depth0,"description") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"description","hash":{},"data":data,"loc":{"start":{"line":10,"column":40},"end":{"line":10,"column":55}}}) : helper)))
    + "</option>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, alias5=container.hooks.blockHelperMissing, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div class=\"popupCustom\">\n    <div class=\"form-group\">\n        <div class=\"row\">\n            <label class=\"col\" for=\"enemy_pack_edit_popup_teeming_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":4,"column":66},"end":{"line":4,"column":72}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"teeming_label") || (depth0 != null ? lookupProperty(depth0,"teeming_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"teeming_label","hash":{},"data":data,"loc":{"start":{"line":4,"column":74},"end":{"line":4,"column":91}}}) : helper)))
    + "</label>\n        </div>\n        <select data-live-search=\"true\" id=\"enemy_pack_edit_popup_teeming_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":6,"column":74},"end":{"line":6,"column":80}}}) : helper)))
    + "\"\n                name=\"enemy_pack_edit_popup_teeming_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":7,"column":52},"end":{"line":7,"column":58}}}) : helper)))
    + "\"\n                class=\"selectpicker popup_select\" data-width=\"300px\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"teeming") || (depth0 != null ? lookupProperty(depth0,"teeming") : depth0)) != null ? helper : alias2),(options={"name":"teeming","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":9,"column":12},"end":{"line":11,"column":24}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"teeming")) { stack1 = alias5.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  buffer += "        </select>\n    </div>\n    <div class=\"form-group\">\n        <div class=\"row\">\n            <label class=\"col\" for=\"enemy_pack_edit_popup_faction_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":16,"column":66},"end":{"line":16,"column":72}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"faction_label") || (depth0 != null ? lookupProperty(depth0,"faction_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"faction_label","hash":{},"data":data,"loc":{"start":{"line":16,"column":74},"end":{"line":16,"column":91}}}) : helper)))
    + "</label>\n        </div>\n        <select data-live-search=\"true\" id=\"enemy_pack_edit_popup_faction_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":18,"column":74},"end":{"line":18,"column":80}}}) : helper)))
    + "\"\n                name=\"enemy_pack_edit_popup_faction_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":19,"column":52},"end":{"line":19,"column":58}}}) : helper)))
    + "\"\n                class=\"selectpicker popup_select\" data-width=\"300px\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"factions") || (depth0 != null ? lookupProperty(depth0,"factions") : depth0)) != null ? helper : alias2),(options={"name":"factions","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":21,"column":12},"end":{"line":23,"column":25}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"factions")) { stack1 = alias5.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "        </select>\n    </div>\n    <div class=\"form-group\">\n        <div class=\"row\">\n            <label class=\"col\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"beguiling_npcs_label") || (depth0 != null ? lookupProperty(depth0,"beguiling_npcs_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"beguiling_npcs_label","hash":{},"data":data,"loc":{"start":{"line":28,"column":31},"end":{"line":28,"column":55}}}) : helper)))
    + "</label>\n        </div>\n        <div class=\"row no-gutters\">\n            <div class=\"col-4\">\n                "
    + alias4(((helper = (helper = lookupProperty(helpers,"preset_label") || (depth0 != null ? lookupProperty(depth0,"preset_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"preset_label","hash":{},"data":data,"loc":{"start":{"line":32,"column":16},"end":{"line":32,"column":32}}}) : helper)))
    + "\n            </div>\n            <div class=\"col-8\">\n                "
    + alias4(((helper = (helper = lookupProperty(helpers,"npc_label") || (depth0 != null ? lookupProperty(depth0,"npc_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_label","hash":{},"data":data,"loc":{"start":{"line":35,"column":16},"end":{"line":35,"column":29}}}) : helper)))
    + "\n            </div>\n        </div>\n        <div id=\"enemy_pack_edit_popup_beguiling_container_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":38,"column":59},"end":{"line":38,"column":65}}}) : helper)))
    + "\">\n\n        </div>\n        <button id=\"enemy_pack_edit_popup_beguiling_add_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":41,"column":56},"end":{"line":41,"column":62}}}) : helper)))
    + "\" class=\"btn btn-success mt-2\">\n            "
    + alias4(((helper = (helper = lookupProperty(helpers,"add_label") || (depth0 != null ? lookupProperty(depth0,"add_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"add_label","hash":{},"data":data,"loc":{"start":{"line":42,"column":12},"end":{"line":42,"column":25}}}) : helper)))
    + "\n        </button>\n    </div>\n    <input type=\"submit\" id=\"enemy_pack_edit_popup_submit_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":45,"column":58},"end":{"line":45,"column":64}}}) : helper)))
    + "\" class=\"btn btn-info\"/>\n</div>";
},"useData":true});
templates['map_enemy_patrol_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "                <option value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"key") || (depth0 != null ? lookupProperty(depth0,"key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"key","hash":{},"data":data,"loc":{"start":{"line":9,"column":31},"end":{"line":9,"column":38}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"description") || (depth0 != null ? lookupProperty(depth0,"description") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"description","hash":{},"data":data,"loc":{"start":{"line":9,"column":40},"end":{"line":9,"column":55}}}) : helper)))
    + "</option>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "            <option value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"key") || (depth0 != null ? lookupProperty(depth0,"key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"key","hash":{},"data":data,"loc":{"start":{"line":19,"column":27},"end":{"line":19,"column":34}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"description") || (depth0 != null ? lookupProperty(depth0,"description") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"description","hash":{},"data":data,"loc":{"start":{"line":19,"column":36},"end":{"line":19,"column":51}}}) : helper)))
    + "</option>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, alias5=container.hooks.blockHelperMissing, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div class=\"popupCustom\">\n\n    <div class=\"form-group\">\n        <label for=\"enemy_patrol_edit_popup_teeming_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":4,"column":52},"end":{"line":4,"column":58}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"teeming_label") || (depth0 != null ? lookupProperty(depth0,"teeming_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"teeming_label","hash":{},"data":data,"loc":{"start":{"line":4,"column":60},"end":{"line":4,"column":77}}}) : helper)))
    + "</label>\n        <select data-live-search=\"true\" id=\"enemy_patrol_edit_popup_teeming_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":5,"column":76},"end":{"line":5,"column":82}}}) : helper)))
    + "\"\n                name=\"enemy_patrol_edit_popup_teeming_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":6,"column":54},"end":{"line":6,"column":60}}}) : helper)))
    + "\"\n                class=\"selectpicker popup_select\" data-width=\"300px\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"teeming") || (depth0 != null ? lookupProperty(depth0,"teeming") : depth0)) != null ? helper : alias2),(options={"name":"teeming","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":8,"column":12},"end":{"line":10,"column":24}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"teeming")) { stack1 = alias5.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  buffer += "        </select>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"enemy_patrol_edit_popup_faction_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":14,"column":52},"end":{"line":14,"column":58}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"faction_label") || (depth0 != null ? lookupProperty(depth0,"faction_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"faction_label","hash":{},"data":data,"loc":{"start":{"line":14,"column":60},"end":{"line":14,"column":77}}}) : helper)))
    + "</label>\n        <select data-live-search=\"true\" id=\"enemy_patrol_edit_popup_faction_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":15,"column":76},"end":{"line":15,"column":82}}}) : helper)))
    + "\"\n                name=\"enemy_patrol_edit_popup_faction_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":16,"column":54},"end":{"line":16,"column":60}}}) : helper)))
    + "\"\n                class=\"selectpicker popup_select\" data-width=\"300px\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"factions") || (depth0 != null ? lookupProperty(depth0,"factions") : depth0)) != null ? helper : alias2),(options={"name":"factions","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":18,"column":12},"end":{"line":20,"column":25}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"factions")) { stack1 = alias5.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "        </select>\n    </div>\n    <input type=\"submit\" id=\"enemy_patrol_edit_popup_submit_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":23,"column":60},"end":{"line":23,"column":66}}}) : helper)))
    + "\" class=\"btn btn-info\"/>\n</div>";
},"useData":true});
templates['map_enemy_raid_marker_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<ul id=\"map_enemy_raid_marker_radial_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":1,"column":37},"end":{"line":1,"column":43}}}) : helper)))
    + "\" class=\"map_enemy_raid_marker_circle_menu\">\n    <li>\n        <a href=\"#\">\n        </a>\n    </li>\n    <li data-name=\"\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title_raid_marker_no_selection") || (depth0 != null ? lookupProperty(depth0,"title_raid_marker_no_selection") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title_raid_marker_no_selection","hash":{},"data":data,"loc":{"start":{"line":6,"column":28},"end":{"line":6,"column":64}}}) : helper)))
    + "\" data-toggle=\"tooltip\" class=\"map_enemy_raid_marker_item\">\n        <a href=\"#\"><i class=\"fas fa-ban\"></i></a>\n    </li>\n    <li data-name=\"star\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title_raid_marker_star") || (depth0 != null ? lookupProperty(depth0,"title_raid_marker_star") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title_raid_marker_star","hash":{},"data":data,"loc":{"start":{"line":9,"column":32},"end":{"line":9,"column":60}}}) : helper)))
    + "\" data-toggle=\"tooltip\" class=\"map_enemy_raid_marker_item\">\n        <a href=\"#\">\n            <div class=\"star_enemy_icon\" style=\"\"></div>\n        </a>\n    </li>\n    <li data-name=\"circle\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title_raid_marker_circle") || (depth0 != null ? lookupProperty(depth0,"title_raid_marker_circle") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title_raid_marker_circle","hash":{},"data":data,"loc":{"start":{"line":14,"column":34},"end":{"line":14,"column":64}}}) : helper)))
    + "\" data-toggle=\"tooltip\" class=\"map_enemy_raid_marker_item\">\n        <a href=\"#\">\n            <div class=\"circle_enemy_icon\" style=\"\"></div>\n        </a>\n    </li>\n    <li data-name=\"diamond\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title_raid_marker_diamond") || (depth0 != null ? lookupProperty(depth0,"title_raid_marker_diamond") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title_raid_marker_diamond","hash":{},"data":data,"loc":{"start":{"line":19,"column":35},"end":{"line":19,"column":66}}}) : helper)))
    + "\" data-toggle=\"tooltip\" class=\"map_enemy_raid_marker_item\">\n        <a href=\"#\">\n            <div class=\"diamond_enemy_icon\" style=\"\"></div>\n        </a>\n    </li>\n    <li data-name=\"triangle\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title_raid_marker_triangle") || (depth0 != null ? lookupProperty(depth0,"title_raid_marker_triangle") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title_raid_marker_triangle","hash":{},"data":data,"loc":{"start":{"line":24,"column":36},"end":{"line":24,"column":68}}}) : helper)))
    + "\" data-toggle=\"tooltip\" class=\"map_enemy_raid_marker_item\">\n        <a href=\"#\">\n            <div class=\"triangle_enemy_icon\" style=\"\"></div>\n        </a>\n    </li>\n    <li data-name=\"moon\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title_raid_marker_moon") || (depth0 != null ? lookupProperty(depth0,"title_raid_marker_moon") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title_raid_marker_moon","hash":{},"data":data,"loc":{"start":{"line":29,"column":32},"end":{"line":29,"column":60}}}) : helper)))
    + "\" data-toggle=\"tooltip\" class=\"map_enemy_raid_marker_item\">\n        <a href=\"#\">\n            <div class=\"moon_enemy_icon\" style=\"\"></div>\n        </a>\n    </li>\n    <li data-name=\"square\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title_raid_marker_square") || (depth0 != null ? lookupProperty(depth0,"title_raid_marker_square") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title_raid_marker_square","hash":{},"data":data,"loc":{"start":{"line":34,"column":34},"end":{"line":34,"column":64}}}) : helper)))
    + "\" data-toggle=\"tooltip\" class=\"map_enemy_raid_marker_item\">\n        <a href=\"#\">\n            <div class=\"square_enemy_icon\" style=\"\"></div>\n        </a>\n    </li>\n    <li data-name=\"cross\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title_raid_marker_cross") || (depth0 != null ? lookupProperty(depth0,"title_raid_marker_cross") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title_raid_marker_cross","hash":{},"data":data,"loc":{"start":{"line":39,"column":33},"end":{"line":39,"column":62}}}) : helper)))
    + "\" data-toggle=\"tooltip\" class=\"map_enemy_raid_marker_item\">\n        <a href=\"#\">\n            <div class=\"cross_enemy_icon\" style=\"\"></div>\n        </a>\n    </li>\n    <li data-name=\"skull\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"title_raid_marker_skull") || (depth0 != null ? lookupProperty(depth0,"title_raid_marker_skull") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"title_raid_marker_skull","hash":{},"data":data,"loc":{"start":{"line":44,"column":33},"end":{"line":44,"column":62}}}) : helper)))
    + "\" data-toggle=\"tooltip\" class=\"map_enemy_raid_marker_item\">\n        <a href=\"#\">\n            <div class=\"skull_enemy_icon\" style=\"\"></div>\n        </a>\n    </li>\n</ul>";
},"useData":true});
templates['map_enemy_tooltip_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"teeming_label") || (depth0 != null ? lookupProperty(depth0,"teeming_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"teeming_label","hash":{},"data":data,"loc":{"start":{"line":16,"column":38},"end":{"line":16,"column":57}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"teeming") || (depth0 != null ? lookupProperty(depth0,"teeming") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"teeming","hash":{},"data":data,"loc":{"start":{"line":17,"column":38},"end":{"line":17,"column":51}}}) : helper)))
    + "</div>\n    </div>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <div class=\"row\">\n        <div class=\"col-12 font-weight-bold\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"admin_only_label") || (depth0 != null ? lookupProperty(depth0,"admin_only_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"admin_only_label","hash":{},"data":data,"loc":{"start":{"line":22,"column":45},"end":{"line":22,"column":67}}}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"id_label") || (depth0 != null ? lookupProperty(depth0,"id_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id_label","hash":{},"data":data,"loc":{"start":{"line":25,"column":38},"end":{"line":25,"column":52}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":26,"column":38},"end":{"line":26,"column":46}}}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"seasonal_index_label") || (depth0 != null ? lookupProperty(depth0,"seasonal_index_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"seasonal_index_label","hash":{},"data":data,"loc":{"start":{"line":29,"column":38},"end":{"line":29,"column":64}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"seasonal_index") || (depth0 != null ? lookupProperty(depth0,"seasonal_index") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"seasonal_index","hash":{},"data":data,"loc":{"start":{"line":30,"column":38},"end":{"line":30,"column":58}}}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"faction_label") || (depth0 != null ? lookupProperty(depth0,"faction_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"faction_label","hash":{},"data":data,"loc":{"start":{"line":33,"column":38},"end":{"line":33,"column":57}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"faction") || (depth0 != null ? lookupProperty(depth0,"faction") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"faction","hash":{},"data":data,"loc":{"start":{"line":34,"column":38},"end":{"line":34,"column":51}}}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"size_label") || (depth0 != null ? lookupProperty(depth0,"size_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"size_label","hash":{},"data":data,"loc":{"start":{"line":37,"column":38},"end":{"line":37,"column":54}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"size") || (depth0 != null ? lookupProperty(depth0,"size") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"size","hash":{},"data":data,"loc":{"start":{"line":38,"column":38},"end":{"line":38,"column":48}}}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"npc_id_label") || (depth0 != null ? lookupProperty(depth0,"npc_id_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_id_label","hash":{},"data":data,"loc":{"start":{"line":41,"column":38},"end":{"line":41,"column":56}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"npc_id") || (depth0 != null ? lookupProperty(depth0,"npc_id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_id","hash":{},"data":data,"loc":{"start":{"line":42,"column":38},"end":{"line":42,"column":50}}}) : helper)))
    + " ("
    + alias4(((helper = (helper = lookupProperty(helpers,"npc_id_type") || (depth0 != null ? lookupProperty(depth0,"npc_id_type") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_id_type","hash":{},"data":data,"loc":{"start":{"line":42,"column":52},"end":{"line":42,"column":69}}}) : helper)))
    + ")</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"attached_to_pack_label") || (depth0 != null ? lookupProperty(depth0,"attached_to_pack_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"attached_to_pack_label","hash":{},"data":data,"loc":{"start":{"line":45,"column":38},"end":{"line":45,"column":66}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"attached_to_pack") || (depth0 != null ? lookupProperty(depth0,"attached_to_pack") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"attached_to_pack","hash":{},"data":data,"loc":{"start":{"line":46,"column":38},"end":{"line":46,"column":60}}}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"enemy_id_label") || (depth0 != null ? lookupProperty(depth0,"enemy_id_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_id_label","hash":{},"data":data,"loc":{"start":{"line":49,"column":38},"end":{"line":49,"column":58}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"enemy_id") || (depth0 != null ? lookupProperty(depth0,"enemy_id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_id","hash":{},"data":data,"loc":{"start":{"line":50,"column":38},"end":{"line":50,"column":52}}}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"visual_label") || (depth0 != null ? lookupProperty(depth0,"visual_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"visual_label","hash":{},"data":data,"loc":{"start":{"line":53,"column":38},"end":{"line":53,"column":56}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"visual") || (depth0 != null ? lookupProperty(depth0,"visual") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"visual","hash":{},"data":data,"loc":{"start":{"line":54,"column":38},"end":{"line":54,"column":50}}}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-12 font-weight-bold\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"mdt_label") || (depth0 != null ? lookupProperty(depth0,"mdt_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mdt_label","hash":{},"data":data,"loc":{"start":{"line":57,"column":45},"end":{"line":57,"column":60}}}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"is_mdt_label") || (depth0 != null ? lookupProperty(depth0,"is_mdt_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"is_mdt_label","hash":{},"data":data,"loc":{"start":{"line":60,"column":38},"end":{"line":60,"column":56}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"is_mdt") || (depth0 != null ? lookupProperty(depth0,"is_mdt") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"is_mdt","hash":{},"data":data,"loc":{"start":{"line":61,"column":38},"end":{"line":61,"column":50}}}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"mdt_id_label") || (depth0 != null ? lookupProperty(depth0,"mdt_id_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mdt_id_label","hash":{},"data":data,"loc":{"start":{"line":64,"column":38},"end":{"line":64,"column":56}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"mdt_id") || (depth0 != null ? lookupProperty(depth0,"mdt_id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mdt_id","hash":{},"data":data,"loc":{"start":{"line":65,"column":38},"end":{"line":65,"column":50}}}) : helper)))
    + "</div>\n    </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"map_enemy_tooltip leaflet-draw-section\">\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"npc_name_label") || (depth0 != null ? lookupProperty(depth0,"npc_name_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_name_label","hash":{},"data":data,"loc":{"start":{"line":3,"column":38},"end":{"line":3,"column":58}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"npc_name") || (depth0 != null ? lookupProperty(depth0,"npc_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_name","hash":{},"data":data,"loc":{"start":{"line":4,"column":38},"end":{"line":4,"column":52}}}) : helper)))
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"enemy_forces_label") || (depth0 != null ? lookupProperty(depth0,"enemy_forces_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_forces_label","hash":{},"data":data,"loc":{"start":{"line":7,"column":38},"end":{"line":7,"column":62}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"enemy_forces") || (depth0 != null ? lookupProperty(depth0,"enemy_forces") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_forces","hash":{},"data":data,"loc":{"start":{"line":8,"column":38},"end":{"line":8,"column":58}}}) : helper))) != null ? stack1 : "")
    + "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-5 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"base_health_label") || (depth0 != null ? lookupProperty(depth0,"base_health_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"base_health_label","hash":{},"data":data,"loc":{"start":{"line":11,"column":38},"end":{"line":11,"column":61}}}) : helper)))
    + "</div>\n        <div class=\"col-7 no-gutters\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"base_health") || (depth0 != null ? lookupProperty(depth0,"base_health") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"base_health","hash":{},"data":data,"loc":{"start":{"line":12,"column":38},"end":{"line":12,"column":55}}}) : helper)))
    + "</div>\n    </div>\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"is_teeming") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":14,"column":4},"end":{"line":19,"column":11}}})) != null ? stack1 : "")
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"is_user_admin") : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":20,"column":4},"end":{"line":67,"column":11}}})) != null ? stack1 : "")
    + "</div>";
},"useData":true});
templates['map_enemy_visuals_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "                <option value=\"npc_class\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"npc_class_label") || (depth0 != null ? lookupProperty(depth0,"npc_class_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_class_label","hash":{},"data":data,"loc":{"start":{"line":6,"column":42},"end":{"line":6,"column":63}}}) : helper)))
    + "</option>\n                <option value=\"npc_type\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"npc_type_label") || (depth0 != null ? lookupProperty(depth0,"npc_type_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"npc_type_label","hash":{},"data":data,"loc":{"start":{"line":7,"column":41},"end":{"line":7,"column":61}}}) : helper)))
    + "</option>\n                <option value=\"enemy_forces\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"enemy_forces_label") || (depth0 != null ? lookupProperty(depth0,"enemy_forces_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_forces_label","hash":{},"data":data,"loc":{"start":{"line":8,"column":45},"end":{"line":8,"column":69}}}) : helper)))
    + "</option>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "        <div class=\"form-group\">\n            <div class=\"font-weight-bold\">"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"mdt_enemy_mapping_label") || (depth0 != null ? lookupProperty(depth0,"mdt_enemy_mapping_label") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"mdt_enemy_mapping_label","hash":{},"data":data,"loc":{"start":{"line":16,"column":42},"end":{"line":16,"column":71}}}) : helper)))
    + ":</div>\n            <input type=\"checkbox\" class=\"form-control left_checkbox\" value=\"1\"\n                   id=\"map_enemy_visuals_map_mdt_clones_to_enemies\"\n                   name=\"map_enemy_visuals_map_mdt_clones_to_enemies\"/>\n        </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"leaflet-draw-section\">\n    <div id=\"map_enemy_visuals\" class=\"form-group\">\n        <div class=\"font-weight-bold\">"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"enemy_display_type_label") || (depth0 != null ? lookupProperty(depth0,"enemy_display_type_label") : depth0)) != null ? helper : alias2),(typeof helper === "function" ? helper.call(alias1,{"name":"enemy_display_type_label","hash":{},"data":data,"loc":{"start":{"line":3,"column":38},"end":{"line":3,"column":68}}}) : helper)))
    + ":</div>\n        <select id=\"map_enemy_visuals_dropdown\" class=\"form-control selectpicker\" name=\"map_enemy_visuals_dropdown\">\n"
    + ((stack1 = (lookupProperty(helpers,"select")||(depth0 && lookupProperty(depth0,"select"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"enemy_visual_type") : depth0),{"name":"select","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":5,"column":12},"end":{"line":9,"column":23}}})) != null ? stack1 : "")
    + "        </select>\n    </div>\n\n\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"is_map_admin") : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":14,"column":4},"end":{"line":21,"column":11}}})) != null ? stack1 : "")
    + "</div>";
},"useData":true});
templates['map_enemy_visual_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <div id=\"map_enemy_visual_attribute_"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":3,"column":40},"end":{"line":3,"column":48}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":3,"column":49},"end":{"line":3,"column":55}}}) : helper)))
    + "\" class=\"modifier "
    + alias4(((helper = (helper = lookupProperty(helpers,"classes") || (depth0 != null ? lookupProperty(depth0,"classes") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"classes","hash":{},"data":data,"loc":{"start":{"line":3,"column":73},"end":{"line":3,"column":84}}}) : helper)))
    + "\" style=\"left: "
    + alias4(((helper = (helper = lookupProperty(helpers,"left") || (depth0 != null ? lookupProperty(depth0,"left") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"left","hash":{},"data":data,"loc":{"start":{"line":3,"column":99},"end":{"line":3,"column":107}}}) : helper)))
    + "px; top: "
    + alias4(((helper = (helper = lookupProperty(helpers,"top") || (depth0 != null ? lookupProperty(depth0,"top") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"top","hash":{},"data":data,"loc":{"start":{"line":3,"column":116},"end":{"line":3,"column":123}}}) : helper)))
    + "px;\">\n        "
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"html") || (depth0 != null ? lookupProperty(depth0,"html") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"html","hash":{},"data":data,"loc":{"start":{"line":4,"column":8},"end":{"line":4,"column":18}}}) : helper))) != null ? stack1 : "")
    + "\n    </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"root_classes") || (depth0 != null ? lookupProperty(depth0,"root_classes") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"root_classes","hash":{},"data":data,"loc":{"start":{"line":1,"column":12},"end":{"line":1,"column":28}}}) : helper)))
    + "\" style=\"position: relative;\">\n"
    + ((stack1 = lookupProperty(helpers,"each").call(alias1,(depth0 != null ? lookupProperty(depth0,"modifiers") : depth0),{"name":"each","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":2,"column":4},"end":{"line":6,"column":13}}})) != null ? stack1 : "")
    + "    <div id=\"map_enemy_visual_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":7,"column":30},"end":{"line":7,"column":36}}}) : helper)))
    + "\" class=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"selection_classes_base") || (depth0 != null ? lookupProperty(depth0,"selection_classes_base") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"selection_classes_base","hash":{},"data":data,"loc":{"start":{"line":7,"column":45},"end":{"line":7,"column":71}}}) : helper)))
    + " "
    + alias4(((helper = (helper = lookupProperty(helpers,"selection_classes") || (depth0 != null ? lookupProperty(depth0,"selection_classes") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"selection_classes","hash":{},"data":data,"loc":{"start":{"line":7,"column":72},"end":{"line":7,"column":93}}}) : helper)))
    + "\">\n        <div id=\"map_enemy_visual_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":8,"column":34},"end":{"line":8,"column":40}}}) : helper)))
    + "_outer\" class=\"outer "
    + alias4(((helper = (helper = lookupProperty(helpers,"main_visual_outer_classes") || (depth0 != null ? lookupProperty(depth0,"main_visual_outer_classes") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"main_visual_outer_classes","hash":{},"data":data,"loc":{"start":{"line":8,"column":61},"end":{"line":8,"column":90}}}) : helper)))
    + "\" style=\"border: "
    + alias4(((helper = (helper = lookupProperty(helpers,"outer_border") || (depth0 != null ? lookupProperty(depth0,"outer_border") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"outer_border","hash":{},"data":data,"loc":{"start":{"line":8,"column":107},"end":{"line":8,"column":123}}}) : helper)))
    + ";\">\n            <div id=\"map_enemy_visual_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":9,"column":38},"end":{"line":9,"column":44}}}) : helper)))
    + "_inner\" class=\"inner "
    + alias4(((helper = (helper = lookupProperty(helpers,"main_visual_inner_classes") || (depth0 != null ? lookupProperty(depth0,"main_visual_inner_classes") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"main_visual_inner_classes","hash":{},"data":data,"loc":{"start":{"line":9,"column":65},"end":{"line":9,"column":94}}}) : helper)))
    + "\" style=\"margin: "
    + alias4(((helper = (helper = lookupProperty(helpers,"margin") || (depth0 != null ? lookupProperty(depth0,"margin") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"margin","hash":{},"data":data,"loc":{"start":{"line":9,"column":111},"end":{"line":9,"column":121}}}) : helper)))
    + "px;\">\n                "
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"main_visual_html") || (depth0 != null ? lookupProperty(depth0,"main_visual_html") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"main_visual_html","hash":{},"data":data,"loc":{"start":{"line":10,"column":16},"end":{"line":10,"column":38}}}) : helper))) != null ? stack1 : "")
    + "\n            </div>\n        </div>\n    </div>\n</div>";
},"useData":true});
templates['map_faction_display_controls_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "            <a class=\"map_faction_display_control map_controls_custom\" href=\"#\"\n               data-faction=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"name_lc") || (depth0 != null ? lookupProperty(depth0,"name_lc") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name_lc","hash":{},"data":data,"loc":{"start":{"line":5,"column":29},"end":{"line":5,"column":42}}}) : helper)))
    + "\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":5,"column":51},"end":{"line":5,"column":61}}}) : helper)))
    + "\">\n                <i class=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"fa_class") || (depth0 != null ? lookupProperty(depth0,"fa_class") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"fa_class","hash":{},"data":data,"loc":{"start":{"line":6,"column":26},"end":{"line":6,"column":40}}}) : helper)))
    + " fa-circle radiobutton\" style=\"width: 15px\"></i>\n                <img src=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"icon_url") || (depth0 != null ? lookupProperty(depth0,"icon_url") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"icon_url","hash":{},"data":data,"loc":{"start":{"line":7,"column":26},"end":{"line":7,"column":40}}}) : helper)))
    + "\" class=\"select_icon faction_icon\"\n                     data-toggle=\"tooltip\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":8,"column":50},"end":{"line":8,"column":60}}}) : helper)))
    + "\"/>\n            </a>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div id=\"map_faction_display_controls\" class=\"leaflet-draw-section\">\n    <div class=\"leaflet-draw-toolbar leaflet-bar leaflet-draw-toolbar-top\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"factions") || (depth0 != null ? lookupProperty(depth0,"factions") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"factions","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":8},"end":{"line":10,"column":21}}}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!lookupProperty(helpers,"factions")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "    </div>\n    <ul class=\"leaflet-draw-actions\"></ul>\n</div>";
},"useData":true});
templates['map_killzonessidebar_killzone_row_edit_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":1,"column":39},"end":{"line":1,"column":45}}}) : helper)))
    + "\" class=\"form-group map_killzonessidebar_killzone\" data-id=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":1,"column":105},"end":{"line":1,"column":111}}}) : helper)))
    + "\">\n    <div class=\"card\">\n        <div class=\"card-body selectable\">\n            <div class=\"row no-gutters\">\n                <div class=\"col-auto pt-1\">\n                    <button id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":6,"column":62},"end":{"line":6,"column":68}}}) : helper)))
    + "_expand\" type=\"button\"\n                            class=\"btn btn-primary collapsed\" aria-pressed=\"false\" data-toggle=\"collapse\"\n                            data-target=\"#map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":8,"column":72},"end":{"line":8,"column":78}}}) : helper)))
    + "_toggle_target\">\n                        <i class=\"fa\" aria-hidden=\"true\"></i>\n                        <span id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":10,"column":64},"end":{"line":10,"column":70}}}) : helper)))
    + "_index\" style=\"font-size: 1em\"></span>\n                    </button>\n                </div>\n\n                <div class=\"col p-2\">\n                    <h5 class=\"card-title map_killzonessidebar_killzone_text "
    + alias4(((helper = (helper = lookupProperty(helpers,"text-class") || (depth0 != null ? lookupProperty(depth0,"text-class") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"text-class","hash":{},"data":data,"loc":{"start":{"line":15,"column":77},"end":{"line":15,"column":91}}}) : helper)))
    + " pt-1\">\n                        <span id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":16,"column":64},"end":{"line":16,"column":70}}}) : helper)))
    + "_enemies\"></span>\n                        <span id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":17,"column":64},"end":{"line":17,"column":70}}}) : helper)))
    + "_enemy_forces_container\">\n                            (<span class=\"text-success\">+<span id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":18,"column":97},"end":{"line":18,"column":103}}}) : helper)))
    + "_enemy_forces\"></span></span>)\n                        </span>\n                    </h5>\n                </div>\n\n                <div id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":23,"column":55},"end":{"line":23,"column":61}}}) : helper)))
    + "_has_boss\" class=\"col-auto p-2\"\n                     data-toggle=\"tooltip\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"kill_zone_has_boss_label") || (depth0 != null ? lookupProperty(depth0,"kill_zone_has_boss_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"kill_zone_has_boss_label","hash":{},"data":data,"loc":{"start":{"line":24,"column":50},"end":{"line":24,"column":78}}}) : helper)))
    + "\">\n                    <img class=\"mt-1\" src=\"/images/mapicon/raid_marker_skull.png\" style=\"width: 16px; height: 16px;\" />\n                </div>\n\n                <div id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":28,"column":55},"end":{"line":28,"column":61}}}) : helper)))
    + "_grip\" class=\"col-auto pt-2\" style=\"font-size: 1.3em\">\n                    <i class=\"fas fa-grip-vertical\"></i>\n                </div>\n            </div>\n\n            <div id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":33,"column":51},"end":{"line":33,"column":57}}}) : helper)))
    + "_toggle_target\" class=\"collapse\">\n                <div id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":34,"column":55},"end":{"line":34,"column":61}}}) : helper)))
    + "_enemy_list\" class=\"input-group mb-1\">\n\n                </div>\n\n                <div class=\"row mb-1\">\n                    <div class=\"col\">\n                        <div class=\"input-group\">\n                            <div id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":41,"column":67},"end":{"line":41,"column":73}}}) : helper)))
    + "_kill_area_label\"\n                                 class=\"input-group-prepend mr-1\" data-toggle=\"tooltip\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"add_kill_area_label") || (depth0 != null ? lookupProperty(depth0,"add_kill_area_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"add_kill_area_label","hash":{},"data":data,"loc":{"start":{"line":42,"column":95},"end":{"line":42,"column":118}}}) : helper)))
    + "\"\n                                 data-haskillarea=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"has_kill_area") || (depth0 != null ? lookupProperty(depth0,"has_kill_area") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"has_kill_area","hash":{},"data":data,"loc":{"start":{"line":43,"column":51},"end":{"line":43,"column":68}}}) : helper)))
    + "\">\n                                <button id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":44,"column":74},"end":{"line":44,"column":80}}}) : helper)))
    + "_has_killzone\"\n                                        class=\"btn btn-primary\" data-toggle=\"button\" aria-pressed=\"false\">\n                                    <i class=\"fas fa-bullseye\"></i>\n                                </button>\n                            </div>\n                            <button id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":49,"column":70},"end":{"line":49,"column":76}}}) : helper)))
    + "_color\"\n                                    class=\"btn map_killzonessidebar_color_btn w-100\">\n\n                            </button>\n                            <div class=\"input-group-append ml-1\" data-toggle=\"tooltip\"\n                                 title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"delete_killzone_label") || (depth0 != null ? lookupProperty(depth0,"delete_killzone_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"delete_killzone_label","hash":{},"data":data,"loc":{"start":{"line":54,"column":40},"end":{"line":54,"column":65}}}) : helper)))
    + "\">\n                                <button id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":55,"column":74},"end":{"line":55,"column":80}}}) : helper)))
    + "_delete\" class=\"btn btn-warning\">\n                                    <i class=\"fa fa-trash\"></i>\n                                </button>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n            </div>\n        </div>\n    </div>\n</div>";
},"useData":true});
templates['map_killzonessidebar_killzone_row_enemy_row_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    return "                    <img class=\"mt-1\" src=\"/images/enemymodifiers/awakened.png\" title=\"Awakened\" data-toggle=\"tooltip\" style=\"width: 16px; height: 16px; border-radius: 18px\" />\n";
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? lookupProperty(depth0,"boss") : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.program(6, data, 0),"data":data,"loc":{"start":{"line":7,"column":16},"end":{"line":11,"column":16}}})) != null ? stack1 : "");
},"4":function(container,depth0,helpers,partials,data) {
    return "                    <img class=\"mt-1\" src=\"/images/mapicon/raid_marker_skull.png\" title=\"Boss\" data-toggle=\"tooltip\" style=\"width: 16px; height: 16px;\" />\n";
},"6":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? lookupProperty(depth0,"dangerous") : depth0),{"name":"if","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":9,"column":16},"end":{"line":11,"column":16}}})) != null ? stack1 : "");
},"7":function(container,depth0,helpers,partials,data) {
    return "                    <i class=\"fa fa-exclamation-triangle text-warning\" title=\"Dangerous\" data-toggle=\"tooltip\" style=\"line-height: 1.6\"></i>\n                ";
},"9":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "                    x"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"count") || (depth0 != null ? lookupProperty(depth0,"count") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"count","hash":{},"data":data,"loc":{"start":{"line":19,"column":21},"end":{"line":19,"column":30}}}) : helper)))
    + "\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"row no-gutters w-100 "
    + alias4(((helper = (helper = lookupProperty(helpers,"text-class") || (depth0 != null ? lookupProperty(depth0,"text-class") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"text-class","hash":{},"data":data,"loc":{"start":{"line":1,"column":33},"end":{"line":1,"column":47}}}) : helper)))
    + "\">\n    <div class=\"col\">\n        <div class=\"input-group\">\n            <div class=\"input-group-prepend\" style=\"width: 24px;\">\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"awakened") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(3, data, 0),"data":data,"loc":{"start":{"line":5,"column":16},"end":{"line":11,"column":23}}})) != null ? stack1 : "")
    + "            </div>\n            <div class=\"input-group-prepend\" style=\"width: 24px;\">\n                "
    + alias4(((helper = (helper = lookupProperty(helpers,"enemy_forces") || (depth0 != null ? lookupProperty(depth0,"enemy_forces") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enemy_forces","hash":{},"data":data,"loc":{"start":{"line":14,"column":16},"end":{"line":14,"column":32}}}) : helper)))
    + "\n            </div>\n            <div style=\"max-width: 200px;\">\n                "
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":17,"column":16},"end":{"line":17,"column":24}}}) : helper)))
    + "\n"
    + ((stack1 = (lookupProperty(helpers,"ifCond")||(depth0 && lookupProperty(depth0,"ifCond"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"count") : depth0),">",1,{"name":"ifCond","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":18,"column":16},"end":{"line":20,"column":27}}})) != null ? stack1 : "")
    + "            </div>\n        </div>\n    </div>\n</div>";
},"useData":true});
templates['map_killzonessidebar_killzone_row_view_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":1,"column":39},"end":{"line":1,"column":45}}}) : helper)))
    + "\" class=\"form-group map_killzonessidebar_killzone\" data-id=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":1,"column":105},"end":{"line":1,"column":111}}}) : helper)))
    + "\">\n    <div class=\"card\">\n        <div class=\"card-body selectable\">\n            <div class=\"row mb-1\">\n                <div class=\"col\">\n                    <div class=\"input-group\">\n                        <div class=\"input-group-append mr-1 map_killzonessidebar_killzone_fill_space\">\n                            <h5 class=\"card-title map_killzonessidebar_killzone_text "
    + alias4(((helper = (helper = lookupProperty(helpers,"text-class") || (depth0 != null ? lookupProperty(depth0,"text-class") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"text-class","hash":{},"data":data,"loc":{"start":{"line":8,"column":85},"end":{"line":8,"column":99}}}) : helper)))
    + "\">\n                                <span id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":9,"column":72},"end":{"line":9,"column":78}}}) : helper)))
    + "_index\"></span>:\n                                <span id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":10,"column":72},"end":{"line":10,"column":78}}}) : helper)))
    + "_enemies\"></span>\n                            </h5>\n                        </div>\n                        <div class=\"input-group-append mr-1\" style=\"width: 32px; background-color: "
    + alias4(((helper = (helper = lookupProperty(helpers,"color") || (depth0 != null ? lookupProperty(depth0,"color") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"color","hash":{},"data":data,"loc":{"start":{"line":13,"column":99},"end":{"line":13,"column":108}}}) : helper)))
    + ";\">\n\n                        </div>\n                    </div>\n                </div>\n            </div>\n            <div id=\"map_killzonessidebar_killzone_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":19,"column":51},"end":{"line":19,"column":57}}}) : helper)))
    + "_enemy_list\" class=\"input-group mb-1\">\n\n            </div>\n        </div>\n    </div>\n</div>";
},"useData":true});
templates['map_map_icon_select_option_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"\">\n    <img src=\"/images/mapicon/"
    + alias4(((helper = (helper = lookupProperty(helpers,"key") || (depth0 != null ? lookupProperty(depth0,"key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"key","hash":{},"data":data,"loc":{"start":{"line":2,"column":30},"end":{"line":2,"column":37}}}) : helper)))
    + ".png\"/> "
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":2,"column":45},"end":{"line":2,"column":53}}}) : helper)))
    + "\n</div>";
},"useData":true});
templates['map_map_icon_visual_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"map_icon "
    + alias4(((helper = (helper = lookupProperty(helpers,"key") || (depth0 != null ? lookupProperty(depth0,"key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"key","hash":{},"data":data,"loc":{"start":{"line":1,"column":21},"end":{"line":1,"column":28}}}) : helper)))
    + " "
    + alias4(((helper = (helper = lookupProperty(helpers,"selectedclass") || (depth0 != null ? lookupProperty(depth0,"selectedclass") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"selectedclass","hash":{},"data":data,"loc":{"start":{"line":1,"column":29},"end":{"line":1,"column":46}}}) : helper)))
    + "\" style=\"width: "
    + alias4(((helper = (helper = lookupProperty(helpers,"width") || (depth0 != null ? lookupProperty(depth0,"width") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"width","hash":{},"data":data,"loc":{"start":{"line":1,"column":62},"end":{"line":1,"column":71}}}) : helper)))
    + "px; height: "
    + alias4(((helper = (helper = lookupProperty(helpers,"height") || (depth0 != null ? lookupProperty(depth0,"height") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"height","hash":{},"data":data,"loc":{"start":{"line":1,"column":83},"end":{"line":1,"column":93}}}) : helper)))
    + "px;\">\n    <img src=\"/images/mapicon/"
    + alias4(((helper = (helper = lookupProperty(helpers,"key") || (depth0 != null ? lookupProperty(depth0,"key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"key","hash":{},"data":data,"loc":{"start":{"line":2,"column":30},"end":{"line":2,"column":37}}}) : helper)))
    + ".png\" />\n</div>";
},"useData":true});
templates['map_path_edit_popup_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "            <div class=\"row no-gutters pt-1\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"colors") || (depth0 != null ? lookupProperty(depth0,"colors") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"colors","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":9,"column":16},"end":{"line":14,"column":27}}}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!lookupProperty(helpers,"colors")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "            </div>\n";
},"2":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "                    <div class=\"col map_polyline_edit_popup_class_color border-dark\"\n                         data-color=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"color") || (depth0 != null ? lookupProperty(depth0,"color") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"color","hash":{},"data":data,"loc":{"start":{"line":11,"column":37},"end":{"line":11,"column":46}}}) : helper)))
    + "\"\n                         style=\"background-color: "
    + alias4(((helper = (helper = lookupProperty(helpers,"color") || (depth0 != null ? lookupProperty(depth0,"color") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"color","hash":{},"data":data,"loc":{"start":{"line":12,"column":50},"end":{"line":12,"column":59}}}) : helper)))
    + ";\">\n                    </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div id=\"map_path_edit_popup_inner\" class=\"popupCustom\">\n    <div class=\"form-group\">\n        <label for=\"map_path_edit_popup_color_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":3,"column":46},"end":{"line":3,"column":52}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"color_label") || (depth0 != null ? lookupProperty(depth0,"color_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"color_label","hash":{},"data":data,"loc":{"start":{"line":3,"column":54},"end":{"line":3,"column":69}}}) : helper)))
    + "</label>\n        <input class=\"form-control\" name=\"map_path_edit_popup_color_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":4,"column":68},"end":{"line":4,"column":74}}}) : helper)))
    + "\" type=\"color\"\n               id=\"map_path_edit_popup_color_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":5,"column":45},"end":{"line":5,"column":51}}}) : helper)))
    + "\">\n\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"rows") || (depth0 != null ? lookupProperty(depth0,"rows") : depth0)) != null ? helper : alias2),(options={"name":"rows","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":7,"column":8},"end":{"line":16,"column":17}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"rows")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "    </div>\n    <button id=\"map_path_edit_popup_submit_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":18,"column":43},"end":{"line":18,"column":49}}}) : helper)))
    + "\" class=\"btn btn-info\" type=\"button\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"submit_label") || (depth0 != null ? lookupProperty(depth0,"submit_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"submit_label","hash":{},"data":data,"loc":{"start":{"line":18,"column":86},"end":{"line":18,"column":102}}}) : helper)))
    + "</button>\n</div>";
},"useData":true});
templates['map_popup_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"popupCustom\">\n    "
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"html") || (depth0 != null ? lookupProperty(depth0,"html") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"html","hash":{},"data":data,"loc":{"start":{"line":2,"column":4},"end":{"line":2,"column":14}}}) : helper))) != null ? stack1 : "")
    + "\n    <button id=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":3,"column":20},"end":{"line":3,"column":39}}}) : helper)))
    + "_edit_popup_submit_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":3,"column":58},"end":{"line":3,"column":64}}}) : helper)))
    + "\" class=\"btn btn-info\" type=\"button\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"submit_label") || (depth0 != null ? lookupProperty(depth0,"submit_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"submit_label","hash":{},"data":data,"loc":{"start":{"line":3,"column":101},"end":{"line":3,"column":117}}}) : helper)))
    + "</button>\n</div>";
},"useData":true});
templates['map_popup_type_bool_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    return "           checked=\"checked\"\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"form-group\">\n    <label class=\"font-weight-bold\" for=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":2,"column":45},"end":{"line":2,"column":64}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":2,"column":76},"end":{"line":2,"column":88}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":2,"column":89},"end":{"line":2,"column":95}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"label") || (depth0 != null ? lookupProperty(depth0,"label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"label","hash":{},"data":data,"loc":{"start":{"line":2,"column":97},"end":{"line":2,"column":106}}}) : helper)))
    + ":</label>\n    <input type=\"checkbox\" class=\"form-control left_checkbox\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"value") || (depth0 != null ? lookupProperty(depth0,"value") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"value","hash":{},"data":data,"loc":{"start":{"line":3,"column":69},"end":{"line":3,"column":78}}}) : helper)))
    + "\"\n           id=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":4,"column":19},"end":{"line":4,"column":38}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":4,"column":50},"end":{"line":4,"column":62}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":4,"column":63},"end":{"line":4,"column":69}}}) : helper)))
    + "\"\n           name=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":5,"column":21},"end":{"line":5,"column":40}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":5,"column":52},"end":{"line":5,"column":64}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":5,"column":65},"end":{"line":5,"column":71}}}) : helper)))
    + "\"\n"
    + ((stack1 = (lookupProperty(helpers,"ifCond")||(depth0 && lookupProperty(depth0,"ifCond"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"value") : depth0),"===",1,{"name":"ifCond","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":6,"column":8},"end":{"line":8,"column":19}}})) != null ? stack1 : "")
    + "    />\n</div>";
},"useData":true});
templates['map_popup_type_color_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"form-group\">\n    <label class=\"font-weight-bold\" for=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":2,"column":45},"end":{"line":2,"column":64}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":2,"column":76},"end":{"line":2,"column":88}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":2,"column":89},"end":{"line":2,"column":95}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"label") || (depth0 != null ? lookupProperty(depth0,"label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"label","hash":{},"data":data,"loc":{"start":{"line":2,"column":97},"end":{"line":2,"column":106}}}) : helper)))
    + ":</label>\n    <button id=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":3,"column":20},"end":{"line":3,"column":39}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":3,"column":51},"end":{"line":3,"column":63}}}) : helper)))
    + "_btn_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":3,"column":68},"end":{"line":3,"column":74}}}) : helper)))
    + "\"></button>\n    <input type=\"hidden\" id=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":4,"column":33},"end":{"line":4,"column":52}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":4,"column":64},"end":{"line":4,"column":76}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":4,"column":77},"end":{"line":4,"column":83}}}) : helper)))
    + "\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"value") || (depth0 != null ? lookupProperty(depth0,"value") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"value","hash":{},"data":data,"loc":{"start":{"line":4,"column":92},"end":{"line":4,"column":101}}}) : helper)))
    + "\"/>\n</div>";
},"useData":true});
templates['map_popup_type_select_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"show_default") : depth0),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":6,"column":12},"end":{"line":8,"column":19}}})) != null ? stack1 : "");
  stack1 = ((helper = (helper = lookupProperty(helpers,"values") || (depth0 != null ? lookupProperty(depth0,"values") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"values","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":9,"column":12},"end":{"line":15,"column":23}}}),(typeof helper === "function" ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"values")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer;
},"2":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "                <option value=\"-1\">"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"select_default_label") || (depth0 != null ? lookupProperty(depth0,"select_default_label") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"select_default_label","hash":{},"data":data,"loc":{"start":{"line":7,"column":35},"end":{"line":7,"column":59}}}) : helper)))
    + "</option>\n";
},"4":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? lookupProperty(depth0,"html") : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.program(7, data, 0),"data":data,"loc":{"start":{"line":10,"column":16},"end":{"line":14,"column":23}}})) != null ? stack1 : "");
},"5":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "                    <option data-content=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"html") || (depth0 != null ? lookupProperty(depth0,"html") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"html","hash":{},"data":data,"loc":{"start":{"line":11,"column":42},"end":{"line":11,"column":50}}}) : helper)))
    + "\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":11,"column":59},"end":{"line":11,"column":65}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":11,"column":67},"end":{"line":11,"column":75}}}) : helper)))
    + "</option>\n";
},"7":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "                    <option value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":13,"column":35},"end":{"line":13,"column":41}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":13,"column":43},"end":{"line":13,"column":51}}}) : helper)))
    + "</option>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"form-group\">\n    <label class=\"font-weight-bold\" for=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":2,"column":45},"end":{"line":2,"column":64}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":2,"column":76},"end":{"line":2,"column":88}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":2,"column":89},"end":{"line":2,"column":95}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"label") || (depth0 != null ? lookupProperty(depth0,"label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"label","hash":{},"data":data,"loc":{"start":{"line":2,"column":97},"end":{"line":2,"column":106}}}) : helper)))
    + ":</label>\n    <select id=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":3,"column":20},"end":{"line":3,"column":39}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":3,"column":51},"end":{"line":3,"column":63}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":3,"column":64},"end":{"line":3,"column":70}}}) : helper)))
    + "\" class=\"selectpicker popup_select\"\n            data-live-search=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"live_search") || (depth0 != null ? lookupProperty(depth0,"live_search") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"live_search","hash":{},"data":data,"loc":{"start":{"line":4,"column":30},"end":{"line":4,"column":45}}}) : helper)))
    + "\" data-width=\"300px\">\n"
    + ((stack1 = (lookupProperty(helpers,"select")||(depth0 && lookupProperty(depth0,"select"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"value") : depth0),{"name":"select","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":5,"column":8},"end":{"line":16,"column":19}}})) != null ? stack1 : "")
    + "    </select>\n</div>";
},"useData":true});
templates['map_popup_type_textarea_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"form-group\">\n    <label class=\"font-weight-bold\" for=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":2,"column":45},"end":{"line":2,"column":64}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":2,"column":76},"end":{"line":2,"column":88}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":2,"column":89},"end":{"line":2,"column":95}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"label") || (depth0 != null ? lookupProperty(depth0,"label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"label","hash":{},"data":data,"loc":{"start":{"line":2,"column":97},"end":{"line":2,"column":106}}}) : helper)))
    + ":</label>\n    <textarea class=\"form-control\" cols=\"50\" rows=\"5\"\n              name=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":4,"column":24},"end":{"line":4,"column":43}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":4,"column":55},"end":{"line":4,"column":67}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":4,"column":68},"end":{"line":4,"column":74}}}) : helper)))
    + "\"\n              id=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":5,"column":22},"end":{"line":5,"column":41}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":5,"column":53},"end":{"line":5,"column":65}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":5,"column":66},"end":{"line":5,"column":72}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"value") || (depth0 != null ? lookupProperty(depth0,"value") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"value","hash":{},"data":data,"loc":{"start":{"line":5,"column":74},"end":{"line":5,"column":83}}}) : helper)))
    + "</textarea>\n</div>";
},"useData":true});
templates['map_popup_type_text_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"form-group\">\n    <label class=\"font-weight-bold\" for=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":2,"column":45},"end":{"line":2,"column":64}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":2,"column":76},"end":{"line":2,"column":88}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":2,"column":89},"end":{"line":2,"column":95}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"label") || (depth0 != null ? lookupProperty(depth0,"label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"label","hash":{},"data":data,"loc":{"start":{"line":2,"column":97},"end":{"line":2,"column":106}}}) : helper)))
    + ":</label>\n    <input type=\"text\" class=\"form-control\"\n           id=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":4,"column":19},"end":{"line":4,"column":38}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":4,"column":50},"end":{"line":4,"column":62}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":4,"column":63},"end":{"line":4,"column":69}}}) : helper)))
    + "\"\n           name=\"map_"
    + alias4(((helper = (helper = lookupProperty(helpers,"map_object_name") || (depth0 != null ? lookupProperty(depth0,"map_object_name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"map_object_name","hash":{},"data":data,"loc":{"start":{"line":5,"column":21},"end":{"line":5,"column":40}}}) : helper)))
    + "_edit_popup_"
    + alias4(((helper = (helper = lookupProperty(helpers,"property") || (depth0 != null ? lookupProperty(depth0,"property") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"property","hash":{},"data":data,"loc":{"start":{"line":5,"column":52},"end":{"line":5,"column":64}}}) : helper)))
    + "_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":5,"column":65},"end":{"line":5,"column":71}}}) : helper)))
    + "\"\n           value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"value") || (depth0 != null ? lookupProperty(depth0,"value") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"value","hash":{},"data":data,"loc":{"start":{"line":6,"column":18},"end":{"line":6,"column":27}}}) : helper)))
    + "\"/>\n</div>";
},"useData":true});
templates['map_sidebar_enemy_info_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <div class=\"row\">\r\n        <div class=\"col-6 font-weight-bold\">\r\n            "
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"key") || (depth0 != null ? lookupProperty(depth0,"key") : depth0)) != null ? helper : alias2),(typeof helper === "function" ? helper.call(alias1,{"name":"key","hash":{},"data":data,"loc":{"start":{"line":4,"column":12},"end":{"line":4,"column":19}}}) : helper)))
    + "\r\n        </div>\r\n        <div class=\"col-6\">\r\n"
    + ((stack1 = (lookupProperty(helpers,"ifCond")||(depth0 && lookupProperty(depth0,"ifCond"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"value") : depth0),"===",1,{"name":"ifCond","hash":{},"fn":container.program(2, data, 0),"inverse":container.program(4, data, 0),"data":data,"loc":{"start":{"line":7,"column":12},"end":{"line":15,"column":23}}})) != null ? stack1 : "")
    + "        </div>\r\n    </div>\r\n";
},"2":function(container,depth0,helpers,partials,data) {
    return "                <span class=\"text-success\"><i class=\"fas fa-check-circle\"></i></span>\r\n";
},"4":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = (lookupProperty(helpers,"ifCond")||(depth0 && lookupProperty(depth0,"ifCond"))||container.hooks.helperMissing).call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? lookupProperty(depth0,"value") : depth0),"===",0,{"name":"ifCond","hash":{},"fn":container.program(5, data, 0),"inverse":container.program(7, data, 0),"data":data,"loc":{"start":{"line":10,"column":16},"end":{"line":14,"column":27}}})) != null ? stack1 : "");
},"5":function(container,depth0,helpers,partials,data) {
    return "                    <span class=\"text-danger\"><i class=\"fas fa-times-circle\"></i></span>\r\n";
},"7":function(container,depth0,helpers,partials,data) {
    var stack1, helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "                    "
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"value") || (depth0 != null ? lookupProperty(depth0,"value") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"value","hash":{},"data":data,"loc":{"start":{"line":13,"column":20},"end":{"line":13,"column":31}}}) : helper))) != null ? stack1 : "")
    + "\r\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"each").call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? lookupProperty(depth0,"info") : depth0),{"name":"each","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":18,"column":9}}})) != null ? stack1 : "");
},"useData":true});
templates['release_change_row_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "                <option value=\"-1\">"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"select_category_label") || (depth0 != null ? lookupProperty(depth0,"select_category_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"select_category_label","hash":{},"data":data,"loc":{"start":{"line":8,"column":35},"end":{"line":8,"column":60}}}) : helper)))
    + "</option>\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"categories") || (depth0 != null ? lookupProperty(depth0,"categories") : depth0)) != null ? helper : alias2),(options={"name":"categories","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":9,"column":16},"end":{"line":11,"column":31}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"categories")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer;
},"2":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "                    <option value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":10,"column":35},"end":{"line":10,"column":41}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"category") || (depth0 != null ? lookupProperty(depth0,"category") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"category","hash":{},"data":data,"loc":{"start":{"line":10,"column":43},"end":{"line":10,"column":55}}}) : helper)))
    + "</option>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"row mt-1 change_"
    + alias4(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":1,"column":28},"end":{"line":1,"column":34}}}) : helper)))
    + "\">\n    <div class=\"col-2\">\n        <input type=\"text\" name=\"tickets[]\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"ticket") || (depth0 != null ? lookupProperty(depth0,"ticket") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"ticket","hash":{},"data":data,"loc":{"start":{"line":3,"column":51},"end":{"line":3,"column":61}}}) : helper)))
    + "\" class=\"form-control\"/>\n    </div>\n    <div class=\"col-3\">\n        <select name=\"categories[]\" class=\"selectpicker\" data-width=\"100%\">\n"
    + ((stack1 = (lookupProperty(helpers,"select")||(depth0 && lookupProperty(depth0,"select"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"category") : depth0),{"name":"select","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":7,"column":12},"end":{"line":12,"column":23}}})) != null ? stack1 : "")
    + "        </select>\n    </div>\n    <div class=\"col-6\">\n        <input type=\"text\" name=\"changes[]\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"change") || (depth0 != null ? lookupProperty(depth0,"change") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"change","hash":{},"data":data,"loc":{"start":{"line":16,"column":51},"end":{"line":16,"column":61}}}) : helper)))
    + "\" class=\"form-control\"/>\n    </div>\n    <div class=\"col-1\">\n        <button type=\"button\" class=\"btn btn-danger change_delete_btn\">\n            <i class=\"fas fa-trash\"></i>\n        </button>\n    </div>\n</div>";
},"useData":true});
templates['release_copy_to_discord'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return container.escapeExpression(((helper = (helper = lookupProperty(helpers,"description") || (depth0 != null ? lookupProperty(depth0,"description") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"description","hash":{},"data":data,"loc":{"start":{"line":3,"column":0},"end":{"line":3,"column":15}}}) : helper)))
    + "\r\n\r\n";
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return container.escapeExpression(((helper = (helper = lookupProperty(helpers,"category") || (depth0 != null ? lookupProperty(depth0,"category") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"category","hash":{},"data":data,"loc":{"start":{"line":7,"column":0},"end":{"line":7,"column":12}}}) : helper)))
    + ":\r\n"
    + ((stack1 = lookupProperty(helpers,"each").call(alias1,(depth0 != null ? lookupProperty(depth0,"changes") : depth0),{"name":"each","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":8,"column":0},"end":{"line":10,"column":9}}})) != null ? stack1 : "")
    + "\r\n";
},"4":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "* "
    + ((stack1 = (lookupProperty(helpers,"ifCond")||(depth0 && lookupProperty(depth0,"ifCond"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"ticket_id") : depth0),">",0,{"name":"ifCond","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":9,"column":2},"end":{"line":9,"column":56}}})) != null ? stack1 : "")
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"change") || (depth0 != null ? lookupProperty(depth0,"change") : depth0)) != null ? helper : alias2),(typeof helper === "function" ? helper.call(alias1,{"name":"change","hash":{},"data":data,"loc":{"start":{"line":9,"column":56},"end":{"line":9,"column":66}}}) : helper)))
    + "\r\n";
},"5":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "#"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"ticket_id") || (depth0 != null ? lookupProperty(depth0,"ticket_id") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"ticket_id","hash":{},"data":data,"loc":{"start":{"line":9,"column":31},"end":{"line":9,"column":44}}}) : helper)))
    + " ";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return alias4(((helper = (helper = lookupProperty(helpers,"version") || (depth0 != null ? lookupProperty(depth0,"version") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"version","hash":{},"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":1,"column":13}}}) : helper)))
    + " ("
    + alias4(((helper = (helper = lookupProperty(helpers,"date") || (depth0 != null ? lookupProperty(depth0,"date") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"date","hash":{},"data":data,"loc":{"start":{"line":1,"column":15},"end":{"line":1,"column":25}}}) : helper)))
    + ")(https://keystone.guru/release/"
    + alias4(((helper = (helper = lookupProperty(helpers,"version") || (depth0 != null ? lookupProperty(depth0,"version") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"version","hash":{},"data":data,"loc":{"start":{"line":1,"column":57},"end":{"line":1,"column":70}}}) : helper)))
    + ")\r\n"
    + ((stack1 = (lookupProperty(helpers,"ifCond")||(depth0 && lookupProperty(depth0,"ifCond"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"description") : depth0),"!=",null,{"name":"ifCond","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":2,"column":0},"end":{"line":5,"column":11}}})) != null ? stack1 : "")
    + ((stack1 = lookupProperty(helpers,"each").call(alias1,(depth0 != null ? lookupProperty(depth0,"categories") : depth0),{"name":"each","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":6,"column":0},"end":{"line":12,"column":9}}})) != null ? stack1 : "");
},"useData":true});
templates['release_copy_to_github'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return container.escapeExpression(((helper = (helper = lookupProperty(helpers,"description") || (depth0 != null ? lookupProperty(depth0,"description") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"description","hash":{},"data":data,"loc":{"start":{"line":2,"column":0},"end":{"line":2,"column":15}}}) : helper)))
    + "\r\n\r\n";
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return container.escapeExpression(((helper = (helper = lookupProperty(helpers,"category") || (depth0 != null ? lookupProperty(depth0,"category") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"category","hash":{},"data":data,"loc":{"start":{"line":6,"column":0},"end":{"line":6,"column":12}}}) : helper)))
    + ":\r\n"
    + ((stack1 = lookupProperty(helpers,"each").call(alias1,(depth0 != null ? lookupProperty(depth0,"changes") : depth0),{"name":"each","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":7,"column":0},"end":{"line":9,"column":9}}})) != null ? stack1 : "")
    + "\r\n";
},"4":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "* "
    + ((stack1 = (lookupProperty(helpers,"ifCond")||(depth0 && lookupProperty(depth0,"ifCond"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"ticket_id") : depth0),">",0,{"name":"ifCond","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":8,"column":2},"end":{"line":8,"column":56}}})) != null ? stack1 : "")
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"change") || (depth0 != null ? lookupProperty(depth0,"change") : depth0)) != null ? helper : alias2),(typeof helper === "function" ? helper.call(alias1,{"name":"change","hash":{},"data":data,"loc":{"start":{"line":8,"column":56},"end":{"line":8,"column":66}}}) : helper)))
    + "\r\n";
},"5":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "#"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"ticket_id") || (depth0 != null ? lookupProperty(depth0,"ticket_id") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"ticket_id","hash":{},"data":data,"loc":{"start":{"line":8,"column":31},"end":{"line":8,"column":44}}}) : helper)))
    + " ";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=depth0 != null ? depth0 : (container.nullContext || {}), lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = (lookupProperty(helpers,"ifCond")||(depth0 && lookupProperty(depth0,"ifCond"))||container.hooks.helperMissing).call(alias1,(depth0 != null ? lookupProperty(depth0,"description") : depth0),"!=",null,{"name":"ifCond","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":4,"column":11}}})) != null ? stack1 : "")
    + ((stack1 = lookupProperty(helpers,"each").call(alias1,(depth0 != null ? lookupProperty(depth0,"categories") : depth0),{"name":"each","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":5,"column":0},"end":{"line":11,"column":9}}})) != null ? stack1 : "");
},"useData":true});
templates['release_copy_to_reddit'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return container.escapeExpression(((helper = (helper = lookupProperty(helpers,"description") || (depth0 != null ? lookupProperty(depth0,"description") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"description","hash":{},"data":data,"loc":{"start":{"line":4,"column":0},"end":{"line":4,"column":15}}}) : helper)))
    + "\n\n";
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return container.escapeExpression(((helper = (helper = lookupProperty(helpers,"category") || (depth0 != null ? lookupProperty(depth0,"category") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"category","hash":{},"data":data,"loc":{"start":{"line":8,"column":0},"end":{"line":8,"column":12}}}) : helper)))
    + ":\n"
    + ((stack1 = lookupProperty(helpers,"each").call(alias1,(depth0 != null ? lookupProperty(depth0,"changes") : depth0),{"name":"each","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":9,"column":0},"end":{"line":11,"column":9}}})) != null ? stack1 : "")
    + "\n";
},"4":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = (lookupProperty(helpers,"ifCond")||(depth0 && lookupProperty(depth0,"ifCond"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"ticket_id") : depth0),">",0,{"name":"ifCond","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":10,"column":0},"end":{"line":10,"column":120}}})) != null ? stack1 : "")
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"change") || (depth0 != null ? lookupProperty(depth0,"change") : depth0)) != null ? helper : alias2),(typeof helper === "function" ? helper.call(alias1,{"name":"change","hash":{},"data":data,"loc":{"start":{"line":10,"column":120},"end":{"line":10,"column":130}}}) : helper)))
    + "\n";
},"5":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "* [\\#"
    + alias4(((helper = (helper = lookupProperty(helpers,"ticket_id") || (depth0 != null ? lookupProperty(depth0,"ticket_id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"ticket_id","hash":{},"data":data,"loc":{"start":{"line":10,"column":33},"end":{"line":10,"column":46}}}) : helper)))
    + "](https://github.com/Wotuu/keystone.guru/issues/"
    + alias4(((helper = (helper = lookupProperty(helpers,"ticket_id") || (depth0 != null ? lookupProperty(depth0,"ticket_id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"ticket_id","hash":{},"data":data,"loc":{"start":{"line":10,"column":94},"end":{"line":10,"column":107}}}) : helper)))
    + ") ";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return alias4(((helper = (helper = lookupProperty(helpers,"version") || (depth0 != null ? lookupProperty(depth0,"version") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"version","hash":{},"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":1,"column":13}}}) : helper)))
    + " ("
    + alias4(((helper = (helper = lookupProperty(helpers,"date") || (depth0 != null ? lookupProperty(depth0,"date") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"date","hash":{},"data":data,"loc":{"start":{"line":1,"column":15},"end":{"line":1,"column":25}}}) : helper)))
    + ")\n[https://keystone.guru/release/"
    + alias4(((helper = (helper = lookupProperty(helpers,"version") || (depth0 != null ? lookupProperty(depth0,"version") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"version","hash":{},"data":data,"loc":{"start":{"line":2,"column":31},"end":{"line":2,"column":44}}}) : helper)))
    + "](https://keystone.guru/release/"
    + alias4(((helper = (helper = lookupProperty(helpers,"version") || (depth0 != null ? lookupProperty(depth0,"version") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"version","hash":{},"data":data,"loc":{"start":{"line":2,"column":76},"end":{"line":2,"column":89}}}) : helper)))
    + ")\n"
    + ((stack1 = (lookupProperty(helpers,"ifCond")||(depth0 && lookupProperty(depth0,"ifCond"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"description") : depth0),"!=",null,{"name":"ifCond","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":0},"end":{"line":6,"column":11}}})) != null ? stack1 : "")
    + ((stack1 = lookupProperty(helpers,"each").call(alias1,(depth0 != null ? lookupProperty(depth0,"categories") : depth0),{"name":"each","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":7,"column":0},"end":{"line":13,"column":9}}})) != null ? stack1 : "");
},"useData":true});
templates['routeattributes_row_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <div class=\"float-left\">\n        <div class=\"select_icon route_attribute-"
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":4,"column":48},"end":{"line":4,"column":56}}}) : helper)))
    + " mr-2\" style=\"height: 24px;\" data-toggle=\"tooltip\"\n             title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"description") || (depth0 != null ? lookupProperty(depth0,"description") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"description","hash":{},"data":data,"loc":{"start":{"line":5,"column":20},"end":{"line":5,"column":35}}}) : helper)))
    + "\">\n            &nbsp;\n        </div>\n    </div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div class=\"row no-gutters\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"attributes") || (depth0 != null ? lookupProperty(depth0,"attributes") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"attributes","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":2,"column":4},"end":{"line":9,"column":19}}}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!lookupProperty(helpers,"attributes")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>";
},"useData":true});
templates['team_dungeonroute_table_add_route_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<button class=\"btn btn-success dungeonroute-add-to-this-team\" type=\"button\" data-publickey=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"public_key") || (depth0 != null ? lookupProperty(depth0,"public_key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"public_key","hash":{},"data":data,"loc":{"start":{"line":1,"column":92},"end":{"line":1,"column":106}}}) : helper)))
    + "\">\n    <span class=\"d-xl-none\"><i class=\"fas fa-plus\"></i></span>\n    <span class=\"d-none d-xl-block\"><i class=\"fas fa-plus\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"add_route_label") || (depth0 != null ? lookupProperty(depth0,"add_route_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"add_route_label","hash":{},"data":data,"loc":{"start":{"line":3,"column":64},"end":{"line":3,"column":83}}}) : helper)))
    + "</span>\n</button>";
},"useData":true});
templates['team_dungeonroute_table_remove_route_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<div class=\"dropdown\">\n    <button class=\"btn btn-secondary dropdown-toggle\" type=\"button\" id=\"dropdownMenuButton\" data-toggle=\"dropdown\"\n            aria-haspopup=\"true\" aria-expanded=\"false\">\n        "
    + alias4(((helper = (helper = lookupProperty(helpers,"actions_label") || (depth0 != null ? lookupProperty(depth0,"actions_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"actions_label","hash":{},"data":data,"loc":{"start":{"line":4,"column":8},"end":{"line":4,"column":25}}}) : helper)))
    + "\n    </button>\n    <div class=\"dropdown-menu\" aria-labelledby=\"dropdownMenuButton\">\n        <a class=\"dropdown-item dungeonroute-clone cursor-pointer\" data-publickey=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"public_key") || (depth0 != null ? lookupProperty(depth0,"public_key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"public_key","hash":{},"data":data,"loc":{"start":{"line":7,"column":83},"end":{"line":7,"column":97}}}) : helper)))
    + "\">\n            <i class=\"fas fa-clone\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"clone_to_profile_label") || (depth0 != null ? lookupProperty(depth0,"clone_to_profile_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"clone_to_profile_label","hash":{},"data":data,"loc":{"start":{"line":8,"column":41},"end":{"line":8,"column":67}}}) : helper)))
    + "\n        </a>\n        <a class=\"dropdown-item dungeonroute-clone-to-team cursor-pointer\" data-publickey=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"public_key") || (depth0 != null ? lookupProperty(depth0,"public_key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"public_key","hash":{},"data":data,"loc":{"start":{"line":10,"column":91},"end":{"line":10,"column":105}}}) : helper)))
    + "\">\n            <i class=\"fas fa-clone\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"clone_to_team_label") || (depth0 != null ? lookupProperty(depth0,"clone_to_team_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"clone_to_team_label","hash":{},"data":data,"loc":{"start":{"line":11,"column":41},"end":{"line":11,"column":64}}}) : helper)))
    + "\n        </a>\n        <div class=\"dropdown-divider\"></div>\n        <a class=\"dropdown-item dungeonroute-remove-from-this-team text-danger cursor-pointer\" data-publickey=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"public_key") || (depth0 != null ? lookupProperty(depth0,"public_key") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"public_key","hash":{},"data":data,"loc":{"start":{"line":14,"column":111},"end":{"line":14,"column":125}}}) : helper)))
    + "\">\n            <i class=\"fas fa-minus\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"remove_route_label") || (depth0 != null ? lookupProperty(depth0,"remove_route_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"remove_route_label","hash":{},"data":data,"loc":{"start":{"line":15,"column":41},"end":{"line":15,"column":63}}}) : helper)))
    + "\n        </a>\n    </div>\n</div>\n";
},"useData":true});
templates['team_member_table_actions_self_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<button class=\"btn btn-danger leave_team_btn\" data-userid=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"user_id") || (depth0 != null ? lookupProperty(depth0,"user_id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"user_id","hash":{},"data":data,"loc":{"start":{"line":1,"column":59},"end":{"line":1,"column":70}}}) : helper)))
    + "\">\n    <span class=\"d-xl-none\"><i class=\"fas fa-door-open\"></i></span>\n    <span class=\"d-none d-xl-block\"><i class=\"fas fa-door-open\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"leave_label") || (depth0 != null ? lookupProperty(depth0,"leave_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"leave_label","hash":{},"data":data,"loc":{"start":{"line":3,"column":69},"end":{"line":3,"column":84}}}) : helper)))
    + "</span>\n</button>";
},"useData":true});
templates['team_member_table_actions_template'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<button class=\"btn btn-danger remove_user_btn\" data-userid=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"user_id") || (depth0 != null ? lookupProperty(depth0,"user_id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"user_id","hash":{},"data":data,"loc":{"start":{"line":1,"column":60},"end":{"line":1,"column":71}}}) : helper)))
    + "\">\n    <span class=\"d-xl-none\"><i class=\"fas fa-user-minus\"></i></span>\n    <span class=\"d-none d-xl-block\"><i class=\"fas fa-user-minus\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"remove_label") || (depth0 != null ? lookupProperty(depth0,"remove_label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"remove_label","hash":{},"data":data,"loc":{"start":{"line":3,"column":70},"end":{"line":3,"column":86}}}) : helper)))
    + "</span>\n</button>";
},"useData":true});
templates['team_member_table_permissions_self_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    return "text-primary";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<span class=\""
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"self") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":1,"column":13},"end":{"line":1,"column":44}}})) != null ? stack1 : "")
    + "\">\n    <i class=\"fas "
    + alias4(((helper = (helper = lookupProperty(helpers,"icon") || (depth0 != null ? lookupProperty(depth0,"icon") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"icon","hash":{},"data":data,"loc":{"start":{"line":2,"column":18},"end":{"line":2,"column":26}}}) : helper)))
    + "\"></i> "
    + alias4(((helper = (helper = lookupProperty(helpers,"label") || (depth0 != null ? lookupProperty(depth0,"label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"label","hash":{},"data":data,"loc":{"start":{"line":2,"column":33},"end":{"line":2,"column":42}}}) : helper)))
    + "\n</span>";
},"useData":true});
templates['team_member_table_permissions_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = "";

  stack1 = ((helper = (helper = lookupProperty(helpers,"roles") || (depth0 != null ? lookupProperty(depth0,"roles") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"roles","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":8},"end":{"line":5,"column":18}}}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!lookupProperty(helpers,"roles")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer;
},"2":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "            <option value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":4,"column":27},"end":{"line":4,"column":35}}}) : helper)))
    + "\" data-icon=\"fas "
    + alias4(((helper = (helper = lookupProperty(helpers,"icon") || (depth0 != null ? lookupProperty(depth0,"icon") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"icon","hash":{},"data":data,"loc":{"start":{"line":4,"column":52},"end":{"line":4,"column":60}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"label") || (depth0 != null ? lookupProperty(depth0,"label") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"label","hash":{},"data":data,"loc":{"start":{"line":4,"column":62},"end":{"line":4,"column":71}}}) : helper)))
    + "</option>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<select class=\"form-control selectpicker role_selection\" data-username=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"username") || (depth0 != null ? lookupProperty(depth0,"username") : depth0)) != null ? helper : alias2),(typeof helper === "function" ? helper.call(alias1,{"name":"username","hash":{},"data":data,"loc":{"start":{"line":1,"column":72},"end":{"line":1,"column":84}}}) : helper)))
    + "\">\n"
    + ((stack1 = (lookupProperty(helpers,"select")||(depth0 && lookupProperty(depth0,"select"))||alias2).call(alias1,(depth0 != null ? lookupProperty(depth0,"role") : depth0),{"name":"select","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":2,"column":4},"end":{"line":6,"column":15}}})) != null ? stack1 : "")
    + "</select>";
},"useData":true});
templates['thumbnailcarousel_template'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "    <img src=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"src") || (depth0 != null ? lookupProperty(depth0,"src") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"src","hash":{},"data":data,"loc":{"start":{"line":3,"column":14},"end":{"line":3,"column":21}}}) : helper)))
    + "\" loading=\"lazy\"/>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<div class=\"owl-carousel owl-theme\">\n";
  stack1 = ((helper = (helper = lookupProperty(helpers,"items") || (depth0 != null ? lookupProperty(depth0,"items") : depth0)) != null ? helper : container.hooks.helperMissing),(options={"name":"items","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":2,"column":4},"end":{"line":4,"column":14}}}),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),options) : helper));
  if (!lookupProperty(helpers,"items")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</div>";
},"useData":true});
})();