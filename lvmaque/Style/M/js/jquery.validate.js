!function($){$.extend($.fn,{validate:function(options){if(!this.length)return options&&options.debug&&window.console&&console.warn("Nothing selected, can't validate, returning nothing."),void 0;var validator=$.data(this[0],"validator");return validator?validator:(this.attr("novalidate","novalidate"),validator=new $.validator(options,this[0]),$.data(this[0],"validator",validator),validator.settings.onsubmit&&(this.validateDelegate(":submit","click",function(event){validator.settings.submitHandler&&(validator.submitButton=event.target),$(event.target).hasClass("cancel")&&(validator.cancelSubmit=!0),void 0!==$(event.target).attr("formnovalidate")&&(validator.cancelSubmit=!0)}),this.submit(function(event){function handle(){var hidden;return validator.settings.submitHandler?(validator.submitButton&&(hidden=$("<input type='hidden'/>").attr("name",validator.submitButton.name).val($(validator.submitButton).val()).appendTo(validator.currentForm)),validator.settings.submitHandler.call(validator,validator.currentForm,event),validator.submitButton&&hidden.remove(),!1):!0}return validator.settings.debug&&event.preventDefault(),validator.cancelSubmit?(validator.cancelSubmit=!1,handle()):validator.form()?validator.pendingRequest?(validator.formSubmitted=!0,!1):handle():(validator.focusInvalid(),!1)})),validator)},valid:function(){if($(this[0]).is("form"))return this.validate().form();var valid=!0,validator=$(this[0].form).validate();return this.each(function(){valid=valid&&validator.element(this)}),valid},removeAttrs:function(attributes){var result={},$element=this;return $.each(attributes.split(/\s/),function(index,value){result[value]=$element.attr(value),$element.removeAttr(value)}),result},rules:function(command,argument){var element=this[0];if(command){var settings=$.data(element.form,"validator").settings,staticRules=settings.rules,existingRules=$.validator.staticRules(element);switch(command){case"add":$.extend(existingRules,$.validator.normalizeRule(argument)),delete existingRules.messages,staticRules[element.name]=existingRules,argument.messages&&(settings.messages[element.name]=$.extend(settings.messages[element.name],argument.messages));break;case"remove":if(!argument)return delete staticRules[element.name],existingRules;var filtered={};return $.each(argument.split(/\s/),function(index,method){filtered[method]=existingRules[method],delete existingRules[method]}),filtered}}var data=$.validator.normalizeRules($.extend({},$.validator.classRules(element),$.validator.attributeRules(element),$.validator.dataRules(element),$.validator.staticRules(element)),element);if(data.required){var param=data.required;delete data.required,data=$.extend({required:param},data)}return data}}),$.extend($.expr[":"],{blank:function(a){return!$.trim(""+$(a).val())},filled:function(a){return!!$.trim(""+$(a).val())},unchecked:function(a){return!$(a).prop("checked")}}),$.validator=function(options,form){this.settings=$.extend(!0,{},$.validator.defaults,options),this.currentForm=form,this.init()},$.validator.format=function(source,params){return 1===arguments.length?function(){var args=$.makeArray(arguments);return args.unshift(source),$.validator.format.apply(this,args)}:(arguments.length>2&&params.constructor!==Array&&(params=$.makeArray(arguments).slice(1)),params.constructor!==Array&&(params=[params]),$.each(params,function(i,n){source=source.replace(new RegExp("\\{"+i+"\\}","g"),function(){return n})}),source)},$.extend($.validator,{defaults:{messages:{},groups:{},rules:{},errorClass:"error",validClass:"valid",errorElement:"label",focusInvalid:!0,errorContainer:$([]),errorLabelContainer:$([]),onsubmit:!0,ignore:":hidden",ignoreTitle:!1,onfocusin:function(element){this.lastActive=element,this.settings.focusCleanup&&!this.blockFocusCleanup&&(this.settings.unhighlight&&this.settings.unhighlight.call(this,element,this.settings.errorClass,this.settings.validClass),this.addWrapper(this.errorsFor(element)).hide())},onfocusout:function(element){this.checkable(element)||!(element.name in this.submitted)&&this.optional(element)||this.element(element)},onkeyup:function(element,event){(9!==event.which||""!==this.elementValue(element))&&(element.name in this.submitted||element===this.lastElement)&&this.element(element)},onclick:function(element){element.name in this.submitted?this.element(element):element.parentNode.name in this.submitted&&this.element(element.parentNode)},highlight:function(element,errorClass,validClass){"radio"===element.type?this.findByName(element.name).addClass(errorClass).removeClass(validClass):$(element).addClass(errorClass).removeClass(validClass)},unhighlight:function(element,errorClass,validClass){"radio"===element.type?this.findByName(element.name).removeClass(errorClass).addClass(validClass):$(element).removeClass(errorClass).addClass(validClass)}},setDefaults:function(settings){$.extend($.validator.defaults,settings)},messages:{required:"This field is required.",remote:"Please fix this field.",email:"Please enter a valid email address.",url:"Please enter a valid URL.",date:"Please enter a valid date.",dateISO:"Please enter a valid date (ISO).",number:"Please enter a valid number.",digits:"Please enter only digits.",creditcard:"Please enter a valid credit card number.",equalTo:"Please enter the same value again.",maxlength:$.validator.format("Please enter no more than {0} characters."),minlength:$.validator.format("Please enter at least {0} characters."),rangelength:$.validator.format("Please enter a value between {0} and {1} characters long."),range:$.validator.format("Please enter a value between {0} and {1}."),max:$.validator.format("Please enter a value less than or equal to {0}."),min:$.validator.format("Please enter a value greater than or equal to {0}.")},autoCreateRanges:!1,prototype:{init:function(){function delegate(event){var validator=$.data(this[0].form,"validator"),eventType="on"+event.type.replace(/^validate/,"");validator.settings[eventType]&&validator.settings[eventType].call(validator,this[0],event)}this.labelContainer=$(this.settings.errorLabelContainer),this.errorContext=this.labelContainer.length&&this.labelContainer||$(this.currentForm),this.containers=$(this.settings.errorContainer).add(this.settings.errorLabelContainer),this.submitted={},this.valueCache={},this.pendingRequest=0,this.pending={},this.invalid={},this.reset();var groups=this.groups={};$.each(this.settings.groups,function(key,value){"string"==typeof value&&(value=value.split(/\s/)),$.each(value,function(index,name){groups[name]=key})});var rules=this.settings.rules;$.each(rules,function(key,value){rules[key]=$.validator.normalizeRule(value)}),$(this.currentForm).validateDelegate(":text, [type='password'], [type='file'], select, textarea, [type='number'], [type='search'] ,[type='tel'], [type='url'], [type='email'], [type='datetime'], [type='date'], [type='month'], [type='week'], [type='time'], [type='datetime-local'], [type='range'], [type='color'] ","focusin focusout keyup",delegate).validateDelegate("[type='radio'], [type='checkbox'], select, option","click",delegate),this.settings.invalidHandler&&$(this.currentForm).bind("invalid-form.validate",this.settings.invalidHandler)},form:function(){return this.checkForm(),$.extend(this.submitted,this.errorMap),this.invalid=$.extend({},this.errorMap),this.valid()||$(this.currentForm).triggerHandler("invalid-form",[this]),this.showErrors(),this.valid()},checkForm:function(){this.prepareForm();for(var i=0,elements=this.currentElements=this.elements();elements[i];i++)this.check(elements[i]);return this.valid()},element:function(element){element=this.validationTargetFor(this.clean(element)),this.lastElement=element,this.prepareElement(element),this.currentElements=$(element);var result=this.check(element)!==!1;return result?delete this.invalid[element.name]:this.invalid[element.name]=!0,this.numberOfInvalids()||(this.toHide=this.toHide.add(this.containers)),this.showErrors(),result},showErrors:function(errors){if(errors){$.extend(this.errorMap,errors),this.errorList=[];for(var name in errors)this.errorList.push({message:errors[name],element:this.findByName(name)[0]});this.successList=$.grep(this.successList,function(element){return!(element.name in errors)})}this.settings.showErrors?this.settings.showErrors.call(this,this.errorMap,this.errorList):this.defaultShowErrors()},resetForm:function(){$.fn.resetForm&&$(this.currentForm).resetForm(),this.submitted={},this.lastElement=null,this.prepareForm(),this.hideErrors(),this.elements().removeClass(this.settings.errorClass).removeData("previousValue")},numberOfInvalids:function(){return this.objectLength(this.invalid)},objectLength:function(obj){var count=0;for(var i in obj)count++;return count},hideErrors:function(){this.addWrapper(this.toHide).hide()},valid:function(){return 0===this.size()},size:function(){return this.errorList.length},focusInvalid:function(){if(this.settings.focusInvalid)try{$(this.findLastActive()||this.errorList.length&&this.errorList[0].element||[]).filter(":visible").focus().trigger("focusin")}catch(e){}},findLastActive:function(){var lastActive=this.lastActive;return lastActive&&1===$.grep(this.errorList,function(n){return n.element.name===lastActive.name}).length&&lastActive},elements:function(){var validator=this,rulesCache={};return $(this.currentForm).find("input, select, textarea").not(":submit, :reset, :image, [disabled]").not(this.settings.ignore).filter(function(){return!this.name&&validator.settings.debug&&window.console&&console.error("%o has no name assigned",this),this.name in rulesCache||!validator.objectLength($(this).rules())?!1:(rulesCache[this.name]=!0,!0)})},clean:function(selector){return $(selector)[0]},errors:function(){var errorClass=this.settings.errorClass.replace(" ",".");return $(this.settings.errorElement+"."+errorClass,this.errorContext)},reset:function(){this.successList=[],this.errorList=[],this.errorMap={},this.toShow=$([]),this.toHide=$([]),this.currentElements=$([])},prepareForm:function(){this.reset(),this.toHide=this.errors().add(this.containers)},prepareElement:function(element){this.reset(),this.toHide=this.errorsFor(element)},elementValue:function(element){var type=$(element).attr("type"),val=$(element).val();return"radio"===type||"checkbox"===type?$("input[name='"+$(element).attr("name")+"']:checked").val():"string"==typeof val?val.replace(/\r/g,""):val},check:function(element){element=this.validationTargetFor(this.clean(element));var result,rules=$(element).rules(),dependencyMismatch=!1,val=this.elementValue(element);for(var method in rules){var rule={method:method,parameters:rules[method]};try{if(result=$.validator.methods[method].call(this,val,element,rule.parameters),"dependency-mismatch"===result){dependencyMismatch=!0;continue}if(dependencyMismatch=!1,"pending"===result)return this.toHide=this.toHide.not(this.errorsFor(element)),void 0;if(!result)return this.formatAndAdd(element,rule),!1}catch(e){throw this.settings.debug&&window.console&&console.log("Exception occurred when checking element "+element.id+", check the '"+rule.method+"' method.",e),e}}return dependencyMismatch?void 0:(this.objectLength(rules)&&this.successList.push(element),!0)},customDataMessage:function(element,method){return $(element).data("msg-"+method.toLowerCase())||element.attributes&&$(element).attr("data-msg-"+method.toLowerCase())},customMessage:function(name,method){var m=this.settings.messages[name];return m&&(m.constructor===String?m:m[method])},findDefined:function(){for(var i=0;i<arguments.length;i++)if(void 0!==arguments[i])return arguments[i];return void 0},defaultMessage:function(element,method){return this.findDefined(this.customMessage(element.name,method),this.customDataMessage(element,method),!this.settings.ignoreTitle&&element.title||void 0,$.validator.messages[method],"<strong>Warning: No message defined for "+element.name+"</strong>")},formatAndAdd:function(element,rule){var message=this.defaultMessage(element,rule.method),theregex=/\$?\{(\d+)\}/g;"function"==typeof message?message=message.call(this,rule.parameters,element):theregex.test(message)&&(message=$.validator.format(message.replace(theregex,"{$1}"),rule.parameters)),this.errorList.push({message:message,element:element}),this.errorMap[element.name]=message,this.submitted[element.name]=message},addWrapper:function(toToggle){return this.settings.wrapper&&(toToggle=toToggle.add(toToggle.parent(this.settings.wrapper))),toToggle},defaultShowErrors:function(){var i,elements;for(i=0;this.errorList[i];i++){var error=this.errorList[i];this.settings.highlight&&this.settings.highlight.call(this,error.element,this.settings.errorClass,this.settings.validClass),this.showLabel(error.element,error.message)}if(this.errorList.length&&(this.toShow=this.toShow.add(this.containers)),this.settings.success)for(i=0;this.successList[i];i++)this.showLabel(this.successList[i]);if(this.settings.unhighlight)for(i=0,elements=this.validElements();elements[i];i++)this.settings.unhighlight.call(this,elements[i],this.settings.errorClass,this.settings.validClass);this.toHide=this.toHide.not(this.toShow),this.hideErrors(),this.addWrapper(this.toShow).show()},validElements:function(){return this.currentElements.not(this.invalidElements())},invalidElements:function(){return $(this.errorList).map(function(){return this.element})},showLabel:function(element,message){var label=this.errorsFor(element);label.length?(label.removeClass(this.settings.validClass).addClass(this.settings.errorClass),label.html(message)):(label=$("<"+this.settings.errorElement+">").attr("for",this.idOrName(element)).addClass(this.settings.errorClass).html(message||""),this.settings.wrapper&&(label=label.hide().show().wrap("<"+this.settings.wrapper+"/>").parent()),this.labelContainer.append(label).length||(this.settings.errorPlacement?this.settings.errorPlacement(label,$(element)):label.insertAfter(element))),!message&&this.settings.success&&(label.text(""),"string"==typeof this.settings.success?label.addClass(this.settings.success):this.settings.success(label,element)),this.toShow=this.toShow.add(label)},errorsFor:function(element){var name=this.idOrName(element);return this.errors().filter(function(){return $(this).attr("for")===name})},idOrName:function(element){return this.groups[element.name]||(this.checkable(element)?element.name:element.id||element.name)},validationTargetFor:function(element){return this.checkable(element)&&(element=this.findByName(element.name).not(this.settings.ignore)[0]),element},checkable:function(element){return/radio|checkbox/i.test(element.type)},findByName:function(name){return $(this.currentForm).find("[name='"+name+"']")},getLength:function(value,element){switch(element.nodeName.toLowerCase()){case"select":return $("option:selected",element).length;case"input":if(this.checkable(element))return this.findByName(element.name).filter(":checked").length}return value.length},depend:function(param,element){return this.dependTypes[typeof param]?this.dependTypes[typeof param](param,element):!0},dependTypes:{"boolean":function(param){return param},string:function(param,element){return!!$(param,element.form).length},"function":function(param,element){return param(element)}},optional:function(element){var val=this.elementValue(element);return!$.validator.methods.required.call(this,val,element)&&"dependency-mismatch"},startRequest:function(element){this.pending[element.name]||(this.pendingRequest++,this.pending[element.name]=!0)},stopRequest:function(element,valid){this.pendingRequest--,this.pendingRequest<0&&(this.pendingRequest=0),delete this.pending[element.name],valid&&0===this.pendingRequest&&this.formSubmitted&&this.form()?($(this.currentForm).submit(),this.formSubmitted=!1):!valid&&0===this.pendingRequest&&this.formSubmitted&&($(this.currentForm).triggerHandler("invalid-form",[this]),this.formSubmitted=!1)},previousValue:function(element){return $.data(element,"previousValue")||$.data(element,"previousValue",{old:null,valid:!0,message:this.defaultMessage(element,"remote")})}},classRuleSettings:{required:{required:!0},email:{email:!0},url:{url:!0},date:{date:!0},dateISO:{dateISO:!0},number:{number:!0},digits:{digits:!0},creditcard:{creditcard:!0}},addClassRules:function(className,rules){className.constructor===String?this.classRuleSettings[className]=rules:$.extend(this.classRuleSettings,className)},classRules:function(element){var rules={},classes=$(element).attr("class");return classes&&$.each(classes.split(" "),function(){this in $.validator.classRuleSettings&&$.extend(rules,$.validator.classRuleSettings[this])}),rules},attributeRules:function(element){var rules={},$element=$(element),type=$element[0].getAttribute("type");for(var method in $.validator.methods){var value;"required"===method?(value=$element.get(0).getAttribute(method),""===value&&(value=!0),value=!!value):value=$element.attr(method),/min|max/.test(method)&&(null===type||/number|range|text/.test(type))&&(value=Number(value)),value?rules[method]=value:type===method&&"range"!==type&&(rules[method]=!0)}return rules.maxlength&&/-1|2147483647|524288/.test(rules.maxlength)&&delete rules.maxlength,rules},dataRules:function(element){var method,value,rules={},$element=$(element);for(method in $.validator.methods)value=$element.data("rule-"+method.toLowerCase()),void 0!==value&&(rules[method]=value);return rules},staticRules:function(element){var rules={},validator=$.data(element.form,"validator");return validator.settings.rules&&(rules=$.validator.normalizeRule(validator.settings.rules[element.name])||{}),rules},normalizeRules:function(rules,element){return $.each(rules,function(prop,val){if(val===!1)return delete rules[prop],void 0;if(val.param||val.depends){var keepRule=!0;switch(typeof val.depends){case"string":keepRule=!!$(val.depends,element.form).length;break;case"function":keepRule=val.depends.call(element,element)}keepRule?rules[prop]=void 0!==val.param?val.param:!0:delete rules[prop]}}),$.each(rules,function(rule,parameter){rules[rule]=$.isFunction(parameter)?parameter(element):parameter}),$.each(["minlength","maxlength"],function(){rules[this]&&(rules[this]=Number(rules[this]))}),$.each(["rangelength","range"],function(){var parts;rules[this]&&($.isArray(rules[this])?rules[this]=[Number(rules[this][0]),Number(rules[this][1])]:"string"==typeof rules[this]&&(parts=rules[this].split(/[\s,]+/),rules[this]=[Number(parts[0]),Number(parts[1])]))}),$.validator.autoCreateRanges&&(rules.min&&rules.max&&(rules.range=[rules.min,rules.max],delete rules.min,delete rules.max),rules.minlength&&rules.maxlength&&(rules.rangelength=[rules.minlength,rules.maxlength],delete rules.minlength,delete rules.maxlength)),rules},normalizeRule:function(data){if("string"==typeof data){var transformed={};$.each(data.split(/\s/),function(){transformed[this]=!0}),data=transformed}return data},addMethod:function(name,method,message){$.validator.methods[name]=method,$.validator.messages[name]=void 0!==message?message:$.validator.messages[name],method.length<3&&$.validator.addClassRules(name,$.validator.normalizeRule(name))},methods:{required:function(value,element,param){if(!this.depend(param,element))return"dependency-mismatch";if("select"===element.nodeName.toLowerCase()){var val=$(element).val();return val&&val.length>0}return this.checkable(element)?this.getLength(value,element)>0:$.trim(value).length>0},email:function(value,element){return this.optional(element)||/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i.test(value)},url:function(value,element){return this.optional(element)||/^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(value)},date:function(value,element){return this.optional(element)||!/Invalid|NaN/.test(new Date(value).toString())},dateISO:function(value,element){return this.optional(element)||/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/.test(value)},number:function(value,element){return this.optional(element)||/^-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test(value)},digits:function(value,element){return this.optional(element)||/^\d+$/.test(value)},creditcard:function(value,element){if(this.optional(element))return"dependency-mismatch";if(/[^0-9 \-]+/.test(value))return!1;var nCheck=0,nDigit=0,bEven=!1;value=value.replace(/\D/g,"");for(var n=value.length-1;n>=0;n--){var cDigit=value.charAt(n);nDigit=parseInt(cDigit,10),bEven&&(nDigit*=2)>9&&(nDigit-=9),nCheck+=nDigit,bEven=!bEven}return 0===nCheck%10},minlength:function(value,element,param){var length=$.isArray(value)?value.length:this.getLength($.trim(value),element);return this.optional(element)||length>=param},maxlength:function(value,element,param){var length=$.isArray(value)?value.length:this.getLength($.trim(value),element);return this.optional(element)||param>=length},rangelength:function(value,element,param){var length=$.isArray(value)?value.length:this.getLength($.trim(value),element);return this.optional(element)||length>=param[0]&&length<=param[1]},min:function(value,element,param){return this.optional(element)||value>=param},max:function(value,element,param){return this.optional(element)||param>=value},range:function(value,element,param){return this.optional(element)||value>=param[0]&&value<=param[1]},equalTo:function(value,element,param){var target=$(param);return this.settings.onfocusout&&target.unbind(".validate-equalTo").bind("blur.validate-equalTo",function(){$(element).valid()}),value===target.val()},remote:function(value,element,param){if(this.optional(element))return"dependency-mismatch";var previous=this.previousValue(element);if(this.settings.messages[element.name]||(this.settings.messages[element.name]={}),previous.originalMessage=this.settings.messages[element.name].remote,this.settings.messages[element.name].remote=previous.message,param="string"==typeof param&&{url:param}||param,previous.old===value)return previous.valid;previous.old=value;var validator=this;this.startRequest(element);var data={};return data[element.name]=value,$.ajax($.extend(!0,{url:param,mode:"abort",port:"validate"+element.name,dataType:"json",data:data,success:function(response){validator.settings.messages[element.name].remote=previous.originalMessage;var valid=response===!0||"true"===response;if(valid){var submitted=validator.formSubmitted;validator.prepareElement(element),validator.formSubmitted=submitted,validator.successList.push(element),delete validator.invalid[element.name],validator.showErrors()}else{var errors={},message=response||validator.defaultMessage(element,"remote");errors[element.name]=previous.message=$.isFunction(message)?message(value):message,validator.invalid[element.name]=!0,validator.showErrors(errors)}previous.valid=valid,validator.stopRequest(element,valid)}},param)),"pending"}}}),$.format=$.validator.format}(jQuery),function($){var pendingRequests={};if($.ajaxPrefilter)$.ajaxPrefilter(function(settings,_,xhr){var port=settings.port;"abort"===settings.mode&&(pendingRequests[port]&&pendingRequests[port].abort(),pendingRequests[port]=xhr)});else{var ajax=$.ajax;$.ajax=function(settings){var mode=("mode"in settings?settings:$.ajaxSettings).mode,port=("port"in settings?settings:$.ajaxSettings).port;return"abort"===mode?(pendingRequests[port]&&pendingRequests[port].abort(),pendingRequests[port]=ajax.apply(this,arguments),pendingRequests[port]):ajax.apply(this,arguments)}}}(jQuery),function($){$.extend($.fn,{validateDelegate:function(delegate,type,handler){return this.bind(type,function(event){var target=$(event.target);return target.is(delegate)?handler.apply(target,arguments):void 0})}})}(jQuery),jQuery.validator.addMethod("vcode",function(value){return/^[\da-zA-Z]{4}$/.test(value)},"请输入正确的验证码"),$.validator.addMethod("sensitiveWord",function(value,element){for(var word,valueToLowerCase=value.toLowerCase(),len=sensitiveWord.length,i=0;len>i;i++)if(word=sensitiveWord[i].toLowerCase(),valueToLowerCase.indexOf(word)>=0)return this.optional(element)||!1;return this.optional(element)||!0},"该昵称不允许注册"),$.validator.addMethod("isUserName",function(value,element){return this.optional(element)||/^[\u4e00-\u9fa5_a-zA-Z0-9]+$/.test(value.toLowerCase())},"用户名只能由中文，字母，数字及下划线组成"),$.validator.addMethod("trimEmail",function(value,element){var value=$.trim(value);return this.optional(element)||/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i.test(value)},"邮箱格式错误，请重新输入"),$.validator.addMethod("notStartWithNum",function(value,element){return this.optional(element)||!/^\d/.test($.trim(value))},"用户名不能以数字开头"),$.validator.addMethod("isPwd",function(value,element){return this.optional(element)||/^[a-zA-Z0-9_+=\-@#~,.\[\]()!%^*$]+$/.test(value)},"密码存在非法字符"),$.validator.methods.phone=function(value){var patten=new RegExp(/^1\d{10}$/);return patten.test(value)},$.validator.methods.idCard=function(value){var patten=new RegExp(/^\d{17}[0-9xX]$/);return patten.test(value)},$.validator.methods.chinese=function(value){var patten=new RegExp(/^[·\u4e00-\u9fa5]+$/);return patten.test(value)},$.validator.methods.idCard2=function(value){if(1==$("#card_type").val()){var patten=new RegExp(/^\d{17}[0-9xX]$/);return patten.test(value)}return!0},$.validator.methods.isDifferent=function(value,element,param){return $(param).val()===value?!1:!0};var sensitiveWord=["bitch","shit","falun","tianwang","cdjp","bignews","boxun","chinaliberal","chinamz","chinesenewsnet","cnd","creaders","dafa","dajiyuan","dfdz","dpp","falu","falundafa","flg","freechina","freenet","fuck","GCD","hongzhi","hrichina","huanet","hypermart","incest","jiangdongriji","lihongzhi","making","minghui","minghuinews","nacb","naive","nmis","paper","peacehall","renminbao","renmingbao","rfa","safeweb","sex","svdc","taip","tibetalk","triangleboy","UltraSurf","unixbox","ustibet","voa","wangce","wstaiji","xinsheng","yuming","zhengjian","zhengjianwang","zhenshanren","zhuanfalun","xxx","anime","censor","hentai","[hz]","(hz)","[av]","(av)","[sm]","(sm)","porn","multimedia","toolbar","downloader","女優","小泽玛莉亚","强歼","乱交","色友","婊子","蒲团","美女","女女","喷尿","绝版","三級","武腾兰","凌辱","暴干","诱惑","阴唇","小泽圆","插插","坐交","長瀨愛","川島和津實","草莓牛奶","小澤園","飯島愛","星崎未來","及川奈央","朝河蘭","夕樹舞子","大澤惠","金澤文子","三浦愛佳","伊東","慰安妇","女教師","武藤蘭","学生妹","无毛","猛插","护士","自拍","A片","A级","喷精","偷窥","小穴","大片","被虐","黄色","被迫","被逼","强暴","口技","破处","精液","幼交","狂干","兽交","群交","叶子楣","舒淇","翁虹","大陆","露点","露毛","武藤兰","饭岛爱","波霸","少儿不宜","成人","偷情","叫床","上床","制服","亚热","援交","走光","情色","肉欲","美腿","自摸","18禁","捆绑","丝袜","潮吹","肛交","黄片","群射","内射","少妇","卡通","臭作","薄格","調教","近親","連發","限制","乱伦","母子","偷拍","更衣","無修正","一本道","1Pondo","櫻井","風花","夜勤病栋","菱恝","虐待","激情","麻衣","三级","吐血","三个代表","一党","多党","民主","专政","行房","自慰","吹萧","色狼","胸罩","内裤","底裤","私处","爽死","变态","妹疼","妹痛","弟疼","弟痛","姐疼","姐痛","哥疼","哥痛","同房","打炮","造爱","作爱","做爱","鸡巴","阴茎","阳具","开苞","肛门","阴道","阴蒂","肉棍","肉棒","肉洞","荡妇","阴囊","睾丸","捅你","捅我","插我","插你","插她","插他","干你","干她","干他","射精","口交","屁眼","阴户","阴门","下体","龟头","阴毛","避孕套","你妈逼","大鸡巴","高潮","政治","大法","弟子","大纪元","真善忍","明慧","洪志","红志","洪智","红智","法轮","法论","法沦","法伦","发轮","发论","发沦","发伦","轮功","轮公","轮攻","沦功","沦公","沦攻","论攻","论功","论公","伦攻","伦功","伦公","打倒","民运","六四","台独","王丹","柴玲","李鹏","天安门","江泽民","朱容基","朱镕基","李长春","李瑞环","胡锦涛","魏京生","台湾独立","藏独","西藏独立","疆独","新疆独立","警察","民警","公安","邓小平","大盖帽","革命","武警","黑社会","交警","消防队","刑警","夜总会","妈个","公款","首长","书记","坐台","腐败","城管","暴动","暴乱","李远哲","司法警官","高干","人大","尉健行","李岚清","黄丽满","于幼军","文字狱","宋祖英","自焚","骗局","猫肉","吸储","张五常","张丕林","空难","温家宝","吴邦国","曾庆红","黄菊","罗干","吴官正","贾庆林","专制","三個代表","一黨","多黨","專政","大紀元","紅志","紅智","法輪","法論","法淪","法倫","發輪","發論","發淪","發倫","輪功","輪公","輪攻","淪功","淪公","淪攻","論攻","論功","論公","倫攻","倫功","倫公","民運","台獨","李鵬","天安門","江澤民","朱鎔基","李長春","李瑞環","胡錦濤","臺灣獨立","藏獨","西藏獨立","疆獨","新疆獨立","鄧小平","大蓋帽","黑社會","消防隊","夜總會","媽個","首長","書記","腐敗","暴動","暴亂","李遠哲","高幹","李嵐清","黃麗滿","於幼軍","文字獄","騙局","貓肉","吸儲","張五常","張丕林","空難","溫家寶","吳邦國","曾慶紅","黃菊","羅幹","賈慶林","專制","八九","八老","巴赫","白立朴","白梦","白皮书","保钓","鲍戈","鲍彤","暴政","北大三角地论坛","北韩","北京当局","北京之春","北美自由论坛","博讯","蔡崇国","曹长青","曹刚川","常劲","陈炳基","陈军","陈蒙","陈破空","陈希同","陈小同","陈宣良","陈一谘","陈总统","程凯","程铁军","程真","迟浩田","持不同政见","赤匪","赤化","春夏自由论坛","达赖","大参考","大纪元新闻网","大纪园","大家论坛","大史","大史记","大史纪","大中国论坛","大中华论坛","大众真人真事","戴相龙","弹劾","登辉","邓笑贫","迪里夏提","地下教会","地下刊物","第四代","电视流氓","钓鱼岛","丁关根","丁元","丁子霖","东北独立","东方红时空","东方时空","东南西北论谈","东社","东土耳其斯坦","东西南北论坛","动乱","独裁","独夫","独立台湾会","杜智富","多维","屙民","俄国","发愣","发正念","反封锁技术","反腐败论坛","反攻","反共","反人类","反社会","方励之","方舟子","飞扬论坛","斐得勒","费良勇","分家在","分裂","粉饰太平","风雨神州","风雨神州论坛","封从德","封杀","冯东海","冯素英","佛展千手法","付申奇","傅申奇","傅志寰","高官","高文谦","高薪养廉","高瞻","高自联","戈扬","鸽派","歌功颂德","蛤蟆","个人崇拜","工自联","功法","共产","共党","共匪","共狗","共军","关卓中","贯通两极法","广闻","郭伯雄","郭罗基","郭平","郭岩华","国家安全","国家机密","国军","国贼","韩东方","韩联潮","何德普","何勇","河殇","红灯区","红色恐怖","宏法","洪传","洪吟","洪哲胜","胡紧掏","胡锦滔","胡锦淘","胡景涛","胡平","胡总书记","护法","华建敏","华通时事论坛","华夏文摘","华语世界论坛","华岳时事论坛","黄慈萍","黄祸","黄菊","黄翔","回民暴动","悔过书","鸡毛信文汇","姬胜德","积克馆","基督","贾廷安","贾育台","建国党","江core","江八点","江流氓","江罗","江绵恒","江青","江戏子","江则民","江泽慧","江贼","江贼民","江折民","江猪","江猪媳","江主席","姜春云","将则民","僵贼","僵贼民","讲法","酱猪媳","交班","教养院","接班","揭批书","金尧如","锦涛","禁看","经文","开放杂志","看中国","抗议","邝锦文","劳动教养所","劳改","劳教","老江","老毛","黎安友","李大师","李登辉","李红痔","李宏志","李洪宽","李继耐","李兰菊","李老师","李录","李禄","李少民","李淑娴","李旺阳","李文斌","李小朋","李小鹏","李月月鸟","李志绥","李总理","李总统","连胜德","联总","廉政大论坛","炼功","梁光烈","梁擎墩","两岸关系","两岸三地论坛","两个中国","两会","两会报道","两会新闻","廖锡龙","林保华","林长盛","林樵清","林慎立","凌锋","刘宾深","刘宾雁","刘刚","刘国凯","刘华清","刘俊国","刘凯中","刘千石","刘青","刘山青","刘士贤","刘文胜","刘晓波","刘晓竹","刘永川","流亡","龙虎豹","陆委会","吕京花","吕秀莲","抡功","轮大","罗礼诗","马大维","马良骏","马三家","马时敏","卖国","毛厕洞","毛贼东","美国参考","美国之音","蒙独","蒙古独立","密穴","绵恒","民国","民进党","民联","民意","民意论坛","民阵","民猪","民主墙","民族矛盾","莫伟强","木犀地","木子论坛","南大自由论坛","闹事","倪育贤","你说我说论坛","潘国平","泡沫经济","迫害","祁建","齐墨","钱达","钱国梁","钱其琛","抢粮记","乔石","亲美","钦本立","秦晋","轻舟快讯","情妇","庆红","全国两会","热比娅","热站政论网","人民报","人民内情真相","人民真实","人民之声论坛","人权","瑞士金融大学","善恶有报","上海帮","上海孤儿院","邵家健","神通加持法","沈彤","升天","盛华仁","盛雪","师父","石戈","时代论坛","时事论坛","世界经济导报","事实独立","双十节","水扁","税力","司马晋","司马璐","司徒华","斯诺","四川独立","宋平","宋书元","苏绍智","苏晓康","台盟","台湾狗","台湾建国运动组织","台湾青年独立联盟","台湾政论区","台湾自由联盟","太子党","汤光中","唐柏桥","唐捷","滕文生","天怒","天葬","童屹","统独","统独论坛","统战","屠杀","外交论坛","外交与方略","万润南","万维读者论坛","万晓东","汪岷","王宝森","王炳章","王策","王超华","王辅臣","王刚","王涵万","王沪宁","王军涛","王力雄","王瑞林","王润生","王若望","王希哲","王秀丽","王冶坪","网特","魏新生","温元凯","文革","无界浏览器","吴百益","吴方城","吴弘达","吴宏达","吴仁华","吴学灿","吴学璨","吾尔开希","五不","伍凡","西藏","洗脑","项怀诚","项小吉","小参考","肖强","邪恶","谢长廷","谢选骏","谢中之","辛灏年","新观察论坛","新华举报","新华内情","新华通论坛","新生网","新闻封锁","新语丝","信用危机","邢铮","熊炎","熊焱","修炼","徐邦秦","徐才厚","徐匡迪","徐水良","许家屯","薛伟","学潮","学联","学习班","学运","学自联","雪山狮子","严家其","严家祺","阎明复","颜射","央视内部晚会","杨怀安","杨建利","杨巍","杨月清","杨周","姚月谦","夜话紫禁城","一中一台","义解","亦凡","异见人士","异议人士","易丹轩","易志熹","尹庆民","由喜贵","游行","幼齿","于浩成","余英时","舆论","舆论反制","宇明网","圆满","远志明","岳武","在十月","则民","择民","泽民","贼民","曾培炎","张伯笠","张钢","张宏堡","张林","张万年","张伟国","张昭富","张志清","赵海青","赵南","赵品潞","赵晓微","赵紫阳","哲民","真相","真象","镇压","争鸣论坛","正见网","正义党论坛","郑义","包夜","冰火","插B","操B","处女","打飞机","风骚","黄色电影","激情视频","叫春","狂插","狂操","狂搞","露乳","裸聊","裸体","屁股","强奸","三级片","色情","脱光","脱衣","性爱","性感","性高潮","性交","胸部","艳舞","一夜情","欲望","操你","你他妈","傻逼","傻B","TMD","TNND","TND","法轮功","江氏","李洪志","新唐人","淫靡","淫水","六四事件","迷药","迷昏药","窃听器","六合彩","买卖枪支","退党","三唑仑","麻醉药","麻醉乙醚","短信群发器","帝国之梦","毛一鲜","色情服务","对日强硬","出售枪支","摇头丸","西藏天葬","鬼村","军长发威","PK黑社会","枪决女犯","投毒杀人","强硬发言","出售假币","监听王","昏药","侦探设备","麻醉钢枪","反华","官商勾结","自杀指南","自杀手册","佳静安定片","蒙汗药粉","古方迷香","强效失意药","迷奸药","透视眼镜","远程偷拍","自制手枪","子女任职名单","激情小电影","黄色小电影","色情小电影","天鹅之旅","盘古乐队","高校暴乱","高校群体事件","大学骚乱","高校骚乱","催情药","拍肩神药","春药","窃听器材","身份证生成器","枪决现场","出售手枪","麻醉枪","办理证件","办理文凭","疆独藏独","高干子弟","高干子女","枪支弹药","血腥图片","反政府","禁书","特码","成人片","成人电影","换妻","发票","增值税","迷幻药","隐形","宏志","杀死","发抡","拉登","拉丹","法抡","法囵","法仑","法纶","发仑","发囵","国研新闻邮件","自由运动","法轮大法","淫秽","E周刊","龙卷风","正法","三陪","嫖娼","静坐","政变","造反","独立","发轮功","功友","人民大众时事参考","示威","罢工","大法弟子","印尼伊斯兰祈祷团","中俄边界新约","政治运动","压迫","非典","共产党","反革命","十六大","江独裁","台湾","东突厥斯坦伊斯兰运动","一边一国","回民","中华民国","政治风波","突厥斯坦","简鸿章","联总之声传单","人民报讯","东突","人民真实报道","教徒","推翻","小灵通","操你奶奶","操你妈","falun","IP17908","falong","陈水扁","主席","改革","他妈的","人民真实报导","开放","中俄","边界新约","（诽闻）","印尼依斯兰祈祷团","东突厥斯坦依斯兰运动","本拉登","维吾尔自由运动","国际地质科学联合会","中国民主正义党","www.cdjp.org","民主中国","www.chinamz.org","中国民主同盟","支联会","天安门母亲","张戎","西藏流亡政府","邓力群","龙新民","www.bignews.org","www.boxun.com","也就是博讯","www.cnd.org","www.chinesenewsnet.com","纪元","www.dajiyuan.com","大纪元时报","自由亚洲","www.rfa.org","www.renminbao.com","维基百科","www.lvmaque.com","根敦.确吉尼玛","根敦.确吉","确吉尼玛","西藏论坛","www.tibetalk.com","破网软件","无界","自由门","花园网","我的奋斗","lvmaque","绿麻雀","安投融","毛泽东","王博","谷云","赵春霞","王晓文","孟繁春","习近平","李克强","张德江","俞正声","刘云山","王岐山","张高丽","彭丽媛"];
!
function($) {
    $.extend($.fn, {
        validate: function(options) {
            if (!this.length) return options && options.debug && window.console && console.warn("Nothing selected, can't validate, returning nothing."),
            void 0;
            var validator = $.data(this[0], "validator");
            return validator ? validator: (this.attr("novalidate", "novalidate"), validator = new $.validator(options, this[0]), $.data(this[0], "validator", validator), validator.settings.onsubmit && (this.validateDelegate(":submit", "click",
            function(event) {
                validator.settings.submitHandler && (validator.submitButton = event.target),
                $(event.target).hasClass("cancel") && (validator.cancelSubmit = !0),
                void 0 !== $(event.target).attr("formnovalidate") && (validator.cancelSubmit = !0)
            }), this.submit(function(event) {
                function handle() {
                    var hidden;
                    return validator.settings.submitHandler ? (validator.submitButton && (hidden = $("<input type='hidden'/>").attr("name", validator.submitButton.name).val($(validator.submitButton).val()).appendTo(validator.currentForm)), validator.settings.submitHandler.call(validator, validator.currentForm, event), validator.submitButton && hidden.remove(), !1) : !0
                }
                return validator.settings.debug && event.preventDefault(),
                validator.cancelSubmit ? (validator.cancelSubmit = !1, handle()) : validator.form() ? validator.pendingRequest ? (validator.formSubmitted = !0, !1) : handle() : (validator.focusInvalid(), !1)
            })), validator)
        },
        valid: function() {
            if ($(this[0]).is("form")) return this.validate().form();
            var valid = !0,
            validator = $(this[0].form).validate();
            return this.each(function() {
                valid = valid && validator.element(this)
            }),
            valid
        },
        removeAttrs: function(attributes) {
            var result = {},
            $element = this;
            return $.each(attributes.split(/\s/),
            function(index, value) {
                result[value] = $element.attr(value),
                $element.removeAttr(value)
            }),
            result
        },
        rules: function(command, argument) {
            var element = this[0];
            if (command) {
                var settings = $.data(element.form, "validator").settings,
                staticRules = settings.rules,
                existingRules = $.validator.staticRules(element);
                switch (command) {
                case "add":
                    $.extend(existingRules, $.validator.normalizeRule(argument)),
                    delete existingRules.messages,
                    staticRules[element.name] = existingRules,
                    argument.messages && (settings.messages[element.name] = $.extend(settings.messages[element.name], argument.messages));
                    break;
                case "remove":
                    if (!argument) return delete staticRules[element.name],
                    existingRules;
                    var filtered = {};
                    return $.each(argument.split(/\s/),
                    function(index, method) {
                        filtered[method] = existingRules[method],
                        delete existingRules[method]
                    }),
                    filtered
                }
            }
            var data = $.validator.normalizeRules($.extend({},
            $.validator.classRules(element), $.validator.attributeRules(element), $.validator.dataRules(element), $.validator.staticRules(element)), element);
            if (data.required) {
                var param = data.required;
                delete data.required,
                data = $.extend({
                    required: param
                },
                data)
            }
            return data
        }
    }),
    $.extend($.expr[":"], {
        blank: function(a) {
            return ! $.trim("" + $(a).val())
        },
        filled: function(a) {
            return !! $.trim("" + $(a).val())
        },
        unchecked: function(a) {
            return ! $(a).prop("checked")
        }
    }),
    $.validator = function(options, form) {
        this.settings = $.extend(!0, {},
        $.validator.defaults, options),
        this.currentForm = form,
        this.init()
    },
    $.validator.format = function(source, params) {
        return 1 === arguments.length ?
        function() {
            var args = $.makeArray(arguments);
            return args.unshift(source),
            $.validator.format.apply(this, args)
        }: (arguments.length > 2 && params.constructor !== Array && (params = $.makeArray(arguments).slice(1)), params.constructor !== Array && (params = [params]), $.each(params,
        function(i, n) {
            source = source.replace(new RegExp("\\{" + i + "\\}", "g"),
            function() {
                return n
            })
        }), source)
    },
    $.extend($.validator, {
        defaults: {
            messages: {},
            groups: {},
            rules: {},
            errorClass: "error",
            validClass: "valid",
            errorElement: "label",
            focusInvalid: !0,
            errorContainer: $([]),
            errorLabelContainer: $([]),
            onsubmit: !0,
            ignore: ":hidden",
            ignoreTitle: !1,
            onfocusin: function(element) {
                this.lastActive = element,
                this.settings.focusCleanup && !this.blockFocusCleanup && (this.settings.unhighlight && this.settings.unhighlight.call(this, element, this.settings.errorClass, this.settings.validClass), this.addWrapper(this.errorsFor(element)).hide())
            },
            onfocusout: function(element) {
                this.checkable(element) || !(element.name in this.submitted) && this.optional(element) || this.element(element)
            },
            onkeyup: function(element, event) { (9 !== event.which || "" !== this.elementValue(element)) && (element.name in this.submitted || element === this.lastElement) && this.element(element)
            },
            onclick: function(element) {
                element.name in this.submitted ? this.element(element) : element.parentNode.name in this.submitted && this.element(element.parentNode)
            },
            highlight: function(element, errorClass, validClass) {
                "radio" === element.type ? this.findByName(element.name).addClass(errorClass).removeClass(validClass) : $(element).addClass(errorClass).removeClass(validClass)
            },
            unhighlight: function(element, errorClass, validClass) {
                "radio" === element.type ? this.findByName(element.name).removeClass(errorClass).addClass(validClass) : $(element).removeClass(errorClass).addClass(validClass)
            }
        },
        setDefaults: function(settings) {
            $.extend($.validator.defaults, settings)
        },
        messages: {
            required: "This field is required.",
            remote: "Please fix this field.",
            email: "Please enter a valid email address.",
            url: "Please enter a valid URL.",
            date: "Please enter a valid date.",
            dateISO: "Please enter a valid date (ISO).",
            number: "Please enter a valid number.",
            digits: "Please enter only digits.",
            creditcard: "Please enter a valid credit card number.",
            equalTo: "Please enter the same value again.",
            maxlength: $.validator.format("Please enter no more than {0} characters."),
            minlength: $.validator.format("Please enter at least {0} characters."),
            rangelength: $.validator.format("Please enter a value between {0} and {1} characters long."),
            range: $.validator.format("Please enter a value between {0} and {1}."),
            max: $.validator.format("Please enter a value less than or equal to {0}."),
            min: $.validator.format("Please enter a value greater than or equal to {0}.")
        },
        autoCreateRanges: !1,
        prototype: {
            init: function() {
                function delegate(event) {
                    var validator = $.data(this[0].form, "validator"),
                    eventType = "on" + event.type.replace(/^validate/, "");
                    validator.settings[eventType] && validator.settings[eventType].call(validator, this[0], event)
                }
                this.labelContainer = $(this.settings.errorLabelContainer),
                this.errorContext = this.labelContainer.length && this.labelContainer || $(this.currentForm),
                this.containers = $(this.settings.errorContainer).add(this.settings.errorLabelContainer),
                this.submitted = {},
                this.valueCache = {},
                this.pendingRequest = 0,
                this.pending = {},
                this.invalid = {},
                this.reset();
                var groups = this.groups = {};
                $.each(this.settings.groups,
                function(key, value) {
                    "string" == typeof value && (value = value.split(/\s/)),
                    $.each(value,
                    function(index, name) {
                        groups[name] = key
                    })
                });
                var rules = this.settings.rules;
                $.each(rules,
                function(key, value) {
                    rules[key] = $.validator.normalizeRule(value)
                }),
                $(this.currentForm).validateDelegate(":text, [type='password'], [type='file'], select, textarea, [type='number'], [type='search'] ,[type='tel'], [type='url'], [type='email'], [type='datetime'], [type='date'], [type='month'], [type='week'], [type='time'], [type='datetime-local'], [type='range'], [type='color'] ", "focusin focusout keyup", delegate).validateDelegate("[type='radio'], [type='checkbox'], select, option", "click", delegate),
                this.settings.invalidHandler && $(this.currentForm).bind("invalid-form.validate", this.settings.invalidHandler)
            },
            form: function() {
                return this.checkForm(),
                $.extend(this.submitted, this.errorMap),
                this.invalid = $.extend({},
                this.errorMap),
                this.valid() || $(this.currentForm).triggerHandler("invalid-form", [this]),
                this.showErrors(),
                this.valid()
            },
            checkForm: function() {
                this.prepareForm();
                for (var i = 0,
                elements = this.currentElements = this.elements(); elements[i]; i++) this.check(elements[i]);
                return this.valid()
            },
            element: function(element) {
                element = this.validationTargetFor(this.clean(element)),
                this.lastElement = element,
                this.prepareElement(element),
                this.currentElements = $(element);
                var result = this.check(element) !== !1;
                return result ? delete this.invalid[element.name] : this.invalid[element.name] = !0,
                this.numberOfInvalids() || (this.toHide = this.toHide.add(this.containers)),
                this.showErrors(),
                result
            },
            showErrors: function(errors) {
                if (errors) {
                    $.extend(this.errorMap, errors),
                    this.errorList = [];
                    for (var name in errors) this.errorList.push({
                        message: errors[name],
                        element: this.findByName(name)[0]
                    });
                    this.successList = $.grep(this.successList,
                    function(element) {
                        return ! (element.name in errors)
                    })
                }
                this.settings.showErrors ? this.settings.showErrors.call(this, this.errorMap, this.errorList) : this.defaultShowErrors()
            },
            resetForm: function() {
                $.fn.resetForm && $(this.currentForm).resetForm(),
                this.submitted = {},
                this.lastElement = null,
                this.prepareForm(),
                this.hideErrors(),
                this.elements().removeClass(this.settings.errorClass).removeData("previousValue")
            },
            numberOfInvalids: function() {
                return this.objectLength(this.invalid)
            },
            objectLength: function(obj) {
                var count = 0;
                for (var i in obj) count++;
                return count
            },
            hideErrors: function() {
                this.addWrapper(this.toHide).hide()
            },
            valid: function() {
                return 0 === this.size()
            },
            size: function() {
                return this.errorList.length
            },
            focusInvalid: function() {
                if (this.settings.focusInvalid) try {
                    $(this.findLastActive() || this.errorList.length && this.errorList[0].element || []).filter(":visible").focus().trigger("focusin")
                } catch(e) {}
            },
            findLastActive: function() {
                var lastActive = this.lastActive;
                return lastActive && 1 === $.grep(this.errorList,
                function(n) {
                    return n.element.name === lastActive.name
                }).length && lastActive
            },
            elements: function() {
                var validator = this,
                rulesCache = {};
                return $(this.currentForm).find("input, select, textarea").not(":submit, :reset, :image, [disabled]").not(this.settings.ignore).filter(function() {
                    return ! this.name && validator.settings.debug && window.console && console.error("%o has no name assigned", this),
                    this.name in rulesCache || !validator.objectLength($(this).rules()) ? !1 : (rulesCache[this.name] = !0, !0)
                })
            },
            clean: function(selector) {
                return $(selector)[0]
            },
            errors: function() {
                var errorClass = this.settings.errorClass.replace(" ", ".");
                return $(this.settings.errorElement + "." + errorClass, this.errorContext)
            },
            reset: function() {
                this.successList = [],
                this.errorList = [],
                this.errorMap = {},
                this.toShow = $([]),
                this.toHide = $([]),
                this.currentElements = $([])
            },
            prepareForm: function() {
                this.reset(),
                this.toHide = this.errors().add(this.containers)
            },
            prepareElement: function(element) {
                this.reset(),
                this.toHide = this.errorsFor(element)
            },
            elementValue: function(element) {
                var type = $(element).attr("type"),
                val = $(element).val();
                return "radio" === type || "checkbox" === type ? $("input[name='" + $(element).attr("name") + "']:checked").val() : "string" == typeof val ? val.replace(/\r/g, "") : val
            },
            check: function(element) {
                element = this.validationTargetFor(this.clean(element));
                var result, rules = $(element).rules(),
                dependencyMismatch = !1,
                val = this.elementValue(element);
                for (var method in rules) {
                    var rule = {
                        method: method,
                        parameters: rules[method]
                    };
                    try {
                        if (result = $.validator.methods[method].call(this, val, element, rule.parameters), "dependency-mismatch" === result) {
                            dependencyMismatch = !0;
                            continue
                        }
                        if (dependencyMismatch = !1, "pending" === result) return this.toHide = this.toHide.not(this.errorsFor(element)),
                        void 0;
                        if (!result) return this.formatAndAdd(element, rule),
                        !1
                    } catch(e) {
                        throw this.settings.debug && window.console && console.log("Exception occurred when checking element " + element.id + ", check the '" + rule.method + "' method.", e),
                        e
                    }
                }
                return dependencyMismatch ? void 0 : (this.objectLength(rules) && this.successList.push(element), !0)
            },
            customDataMessage: function(element, method) {
                return $(element).data("msg-" + method.toLowerCase()) || element.attributes && $(element).attr("data-msg-" + method.toLowerCase())
            },
            customMessage: function(name, method) {
                var m = this.settings.messages[name];
                return m && (m.constructor === String ? m: m[method])
            },
            findDefined: function() {
                for (var i = 0; i < arguments.length; i++) if (void 0 !== arguments[i]) return arguments[i];
                return void 0
            },
            defaultMessage: function(element, method) {
                return this.findDefined(this.customMessage(element.name, method), this.customDataMessage(element, method), !this.settings.ignoreTitle && element.title || void 0, $.validator.messages[method], "<strong>Warning: No message defined for " + element.name + "</strong>")
            },
            formatAndAdd: function(element, rule) {
                var message = this.defaultMessage(element, rule.method),
                theregex = /\$?\{(\d+)\}/g;
                "function" == typeof message ? message = message.call(this, rule.parameters, element) : theregex.test(message) && (message = $.validator.format(message.replace(theregex, "{$1}"), rule.parameters)),
                this.errorList.push({
                    message: message,
                    element: element
                }),
                this.errorMap[element.name] = message,
                this.submitted[element.name] = message
            },
            addWrapper: function(toToggle) {
                return this.settings.wrapper && (toToggle = toToggle.add(toToggle.parent(this.settings.wrapper))),
                toToggle
            },
            defaultShowErrors: function() {
                var i, elements;
                for (i = 0; this.errorList[i]; i++) {
                    var error = this.errorList[i];
                    this.settings.highlight && this.settings.highlight.call(this, error.element, this.settings.errorClass, this.settings.validClass),
                    this.showLabel(error.element, error.message)
                }
                if (this.errorList.length && (this.toShow = this.toShow.add(this.containers)), this.settings.success) for (i = 0; this.successList[i]; i++) this.showLabel(this.successList[i]);
                if (this.settings.unhighlight) for (i = 0, elements = this.validElements(); elements[i]; i++) this.settings.unhighlight.call(this, elements[i], this.settings.errorClass, this.settings.validClass);
                this.toHide = this.toHide.not(this.toShow),
                this.hideErrors(),
                this.addWrapper(this.toShow).show()
            },
            validElements: function() {
                return this.currentElements.not(this.invalidElements())
            },
            invalidElements: function() {
                return $(this.errorList).map(function() {
                    return this.element
                })
            },
            showLabel: function(element, message) {
                var label = this.errorsFor(element);
                label.length ? (label.removeClass(this.settings.validClass).addClass(this.settings.errorClass), label.html(message)) : (label = $("<" + this.settings.errorElement + ">").attr("for", this.idOrName(element)).addClass(this.settings.errorClass).html(message || ""), this.settings.wrapper && (label = label.hide().show().wrap("<" + this.settings.wrapper + "/>").parent()), this.labelContainer.append(label).length || (this.settings.errorPlacement ? this.settings.errorPlacement(label, $(element)) : label.insertAfter(element))),
                !message && this.settings.success && (label.text(""), "string" == typeof this.settings.success ? label.addClass(this.settings.success) : this.settings.success(label, element)),
                this.toShow = this.toShow.add(label)
            },
            errorsFor: function(element) {
                var name = this.idOrName(element);
                return this.errors().filter(function() {
                    return $(this).attr("for") === name
                })
            },
            idOrName: function(element) {
                return this.groups[element.name] || (this.checkable(element) ? element.name: element.id || element.name)
            },
            validationTargetFor: function(element) {
                return this.checkable(element) && (element = this.findByName(element.name).not(this.settings.ignore)[0]),
                element
            },
            checkable: function(element) {
                return /radio|checkbox/i.test(element.type)
            },
            findByName: function(name) {
                return $(this.currentForm).find("[name='" + name + "']")
            },
            getLength: function(value, element) {
                switch (element.nodeName.toLowerCase()) {
                case "select":
                    return $("option:selected", element).length;
                case "input":
                    if (this.checkable(element)) return this.findByName(element.name).filter(":checked").length
                }
                return value.length
            },
            depend: function(param, element) {
                return this.dependTypes[typeof param] ? this.dependTypes[typeof param](param, element) : !0
            },
            dependTypes: {
                "boolean": function(param) {
                    return param
                },
                string: function(param, element) {
                    return !! $(param, element.form).length
                },
                "function": function(param, element) {
                    return param(element)
                }
            },
            optional: function(element) {
                var val = this.elementValue(element);
                return ! $.validator.methods.required.call(this, val, element) && "dependency-mismatch"
            },
            startRequest: function(element) {
                this.pending[element.name] || (this.pendingRequest++, this.pending[element.name] = !0)
            },
            stopRequest: function(element, valid) {
                this.pendingRequest--,
                this.pendingRequest < 0 && (this.pendingRequest = 0),
                delete this.pending[element.name],
                valid && 0 === this.pendingRequest && this.formSubmitted && this.form() ? ($(this.currentForm).submit(), this.formSubmitted = !1) : !valid && 0 === this.pendingRequest && this.formSubmitted && ($(this.currentForm).triggerHandler("invalid-form", [this]), this.formSubmitted = !1)
            },
            previousValue: function(element) {
                return $.data(element, "previousValue") || $.data(element, "previousValue", {
                    old: null,
                    valid: !0,
                    message: this.defaultMessage(element, "remote")
                })
            }
        },
        classRuleSettings: {
            required: {
                required: !0
            },
            email: {
                email: !0
            },
            url: {
                url: !0
            },
            date: {
                date: !0
            },
            dateISO: {
                dateISO: !0
            },
            number: {
                number: !0
            },
            digits: {
                digits: !0
            },
            creditcard: {
                creditcard: !0
            }
        },
        addClassRules: function(className, rules) {
            className.constructor === String ? this.classRuleSettings[className] = rules: $.extend(this.classRuleSettings, className)
        },
        classRules: function(element) {
            var rules = {},
            classes = $(element).attr("class");
            return classes && $.each(classes.split(" "),
            function() {
                this in $.validator.classRuleSettings && $.extend(rules, $.validator.classRuleSettings[this])
            }),
            rules
        },
        attributeRules: function(element) {
            var rules = {},
            $element = $(element),
            type = $element[0].getAttribute("type");
            for (var method in $.validator.methods) {
                var value;
                "required" === method ? (value = $element.get(0).getAttribute(method), "" === value && (value = !0), value = !!value) : value = $element.attr(method),
                /min|max/.test(method) && (null === type || /number|range|text/.test(type)) && (value = Number(value)),
                value ? rules[method] = value: type === method && "range" !== type && (rules[method] = !0)
            }
            return rules.maxlength && /-1|2147483647|524288/.test(rules.maxlength) && delete rules.maxlength,
            rules
        },
        dataRules: function(element) {
            var method, value, rules = {},
            $element = $(element);
            for (method in $.validator.methods) value = $element.data("rule-" + method.toLowerCase()),
            void 0 !== value && (rules[method] = value);
            return rules
        },
        staticRules: function(element) {
            var rules = {},
            validator = $.data(element.form, "validator");
            return validator.settings.rules && (rules = $.validator.normalizeRule(validator.settings.rules[element.name]) || {}),
            rules
        },
        normalizeRules: function(rules, element) {
            return $.each(rules,
            function(prop, val) {
                if (val === !1) return delete rules[prop],
                void 0;
                if (val.param || val.depends) {
                    var keepRule = !0;
                    switch (typeof val.depends) {
                    case "string":
                        keepRule = !!$(val.depends, element.form).length;
                        break;
                    case "function":
                        keepRule = val.depends.call(element, element)
                    }
                    keepRule ? rules[prop] = void 0 !== val.param ? val.param: !0 : delete rules[prop]
                }
            }),
            $.each(rules,
            function(rule, parameter) {
                rules[rule] = $.isFunction(parameter) ? parameter(element) : parameter
            }),
            $.each(["minlength", "maxlength"],
            function() {
                rules[this] && (rules[this] = Number(rules[this]))
            }),
            $.each(["rangelength", "range"],
            function() {
                var parts;
                rules[this] && ($.isArray(rules[this]) ? rules[this] = [Number(rules[this][0]), Number(rules[this][1])] : "string" == typeof rules[this] && (parts = rules[this].split(/[\s,]+/), rules[this] = [Number(parts[0]), Number(parts[1])]))
            }),
            $.validator.autoCreateRanges && (rules.min && rules.max && (rules.range = [rules.min, rules.max], delete rules.min, delete rules.max), rules.minlength && rules.maxlength && (rules.rangelength = [rules.minlength, rules.maxlength], delete rules.minlength, delete rules.maxlength)),
            rules
        },
        normalizeRule: function(data) {
            if ("string" == typeof data) {
                var transformed = {};
                $.each(data.split(/\s/),
                function() {
                    transformed[this] = !0
                }),
                data = transformed
            }
            return data
        },
        addMethod: function(name, method, message) {
            $.validator.methods[name] = method,
            $.validator.messages[name] = void 0 !== message ? message: $.validator.messages[name],
            method.length < 3 && $.validator.addClassRules(name, $.validator.normalizeRule(name))
        },
        methods: {
            required: function(value, element, param) {
                if (!this.depend(param, element)) return "dependency-mismatch";
                if ("select" === element.nodeName.toLowerCase()) {
                    var val = $(element).val();
                    return val && val.length > 0
                }
                return this.checkable(element) ? this.getLength(value, element) > 0 : $.trim(value).length > 0
            },
            email: function(value, element) {
                return this.optional(element) || /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i.test(value)
            },
            url: function(value, element) {
                return this.optional(element) || /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(value)
            },
            date: function(value, element) {
                return this.optional(element) || !/Invalid|NaN/.test(new Date(value).toString())
            },
            dateISO: function(value, element) {
                return this.optional(element) || /^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/.test(value)
            },
            number: function(value, element) {
                return this.optional(element) || /^-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test(value)
            },
            digits: function(value, element) {
                return this.optional(element) || /^\d+$/.test(value)
            },
            creditcard: function(value, element) {
                if (this.optional(element)) return "dependency-mismatch";
                if (/[^0-9 \-]+/.test(value)) return ! 1;
                var nCheck = 0,
                nDigit = 0,
                bEven = !1;
                value = value.replace(/\D/g, "");
                for (var n = value.length - 1; n >= 0; n--) {
                    var cDigit = value.charAt(n);
                    nDigit = parseInt(cDigit, 10),
                    bEven && (nDigit *= 2) > 9 && (nDigit -= 9),
                    nCheck += nDigit,
                    bEven = !bEven
                }
                return 0 === nCheck % 10
            },
            minlength: function(value, element, param) {
                var length = $.isArray(value) ? value.length: this.getLength($.trim(value), element);
                return this.optional(element) || length >= param
            },
            maxlength: function(value, element, param) {
                var length = $.isArray(value) ? value.length: this.getLength($.trim(value), element);
                return this.optional(element) || param >= length
            },
            rangelength: function(value, element, param) {
                var length = $.isArray(value) ? value.length: this.getLength($.trim(value), element);
                return this.optional(element) || length >= param[0] && length <= param[1]
            },
            min: function(value, element, param) {
                return this.optional(element) || value >= param
            },
            max: function(value, element, param) {
                return this.optional(element) || param >= value
            },
            range: function(value, element, param) {
                return this.optional(element) || value >= param[0] && value <= param[1]
            },
            equalTo: function(value, element, param) {
                var target = $(param);
                return this.settings.onfocusout && target.unbind(".validate-equalTo").bind("blur.validate-equalTo",
                function() {
                    $(element).valid()
                }),
                value === target.val()
            },
            remote: function(value, element, param) {
                if (this.optional(element)) return "dependency-mismatch";
                var previous = this.previousValue(element);
                if (this.settings.messages[element.name] || (this.settings.messages[element.name] = {}), previous.originalMessage = this.settings.messages[element.name].remote, this.settings.messages[element.name].remote = previous.message, param = "string" == typeof param && {
                    url: param
                } || param, previous.old === value) return previous.valid;
                previous.old = value;
                var validator = this;
                this.startRequest(element);
                var data = {};
                return data[element.name] = value,
                $.ajax($.extend(!0, {
                    url: param,
                    mode: "abort",
                    port: "validate" + element.name,
                    dataType: "json",
                    data: data,
                    success: function(response) {
                        validator.settings.messages[element.name].remote = previous.originalMessage;
                        var valid = response === !0 || "true" === response;
                        if (valid) {
                            var submitted = validator.formSubmitted;
                            validator.prepareElement(element),
                            validator.formSubmitted = submitted,
                            validator.successList.push(element),
                            delete validator.invalid[element.name],
                            validator.showErrors()
                        } else {
                            var errors = {},
                            message = response || validator.defaultMessage(element, "remote");
                            errors[element.name] = previous.message = $.isFunction(message) ? message(value) : message,
                            validator.invalid[element.name] = !0,
                            validator.showErrors(errors)
                        }
                        previous.valid = valid,
                        validator.stopRequest(element, valid)
                    }
                },
                param)),
                "pending"
            }
        }
    }),
    $.format = $.validator.format
} (jQuery),
function($) {
    var pendingRequests = {};
    if ($.ajaxPrefilter) $.ajaxPrefilter(function(settings, _, xhr) {
        var port = settings.port;
        "abort" === settings.mode && (pendingRequests[port] && pendingRequests[port].abort(), pendingRequests[port] = xhr)
    });
    else {
        var ajax = $.ajax;
        $.ajax = function(settings) {
            var mode = ("mode" in settings ? settings: $.ajaxSettings).mode,
            port = ("port" in settings ? settings: $.ajaxSettings).port;
            return "abort" === mode ? (pendingRequests[port] && pendingRequests[port].abort(), pendingRequests[port] = ajax.apply(this, arguments), pendingRequests[port]) : ajax.apply(this, arguments)
        }
    }
} (jQuery),
function($) {
    $.extend($.fn, {
        validateDelegate: function(delegate, type, handler) {
            return this.bind(type,
            function(event) {
                var target = $(event.target);
                return target.is(delegate) ? handler.apply(target, arguments) : void 0
            })
        }
    })
} (jQuery),
jQuery.validator.addMethod("vcode",
function(value) {
    return /^[\da-zA-Z]{4}$/.test(value)
},
"请输入正确的验证码"),
$.validator.addMethod("sensitiveWord",
function(value, element) {
    for (var word, valueToLowerCase = value.toLowerCase(), len = sensitiveWord.length, i = 0; len > i; i++) if (word = sensitiveWord[i].toLowerCase(), valueToLowerCase.indexOf(word) >= 0) return this.optional(element) || !1;
    return this.optional(element) || !0
},
"该昵称不允许注册"),
$.validator.addMethod("isUserName",
function(value, element) {
    return this.optional(element) || /^[\u4e00-\u9fa5_a-zA-Z0-9]+$/.test(value.toLowerCase())
},
"用户名只能由中文，字母，数字及下划线组成"),
$.validator.addMethod("trimEmail",
function(value, element) {
    var value = $.trim(value);
    return this.optional(element) || /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i.test(value)
},
"邮箱格式错误，请重新输入"),
$.validator.addMethod("notStartWithNum",
function(value, element) {
    return this.optional(element) || !/^\d/.test($.trim(value))
},
"用户名不能以数字开头"),
$.validator.addMethod("isPwd",
function(value, element) {
    return this.optional(element) || /^[a-zA-Z0-9_+=\-@#~,.\[\]()!%^*$]+$/.test(value)
},
"密码存在非法字符"),
$.validator.methods.phone = function(value) {
    var patten = new RegExp(/^1\d{10}$/);
    return patten.test(value)
},
$.validator.methods.idCard = function(value) {
    var patten = new RegExp(/^\d{17}[0-9xX]$/);
    return patten.test(value)
},
$.validator.methods.chinese = function(value) {
    var patten = new RegExp(/^[·\u4e00-\u9fa5]+$/);
    return patten.test(value)
},
$.validator.methods.idCard2 = function(value) {
    if (1 == $("#card_type").val()) {
        var patten = new RegExp(/^\d{17}[0-9xX]$/);
        return patten.test(value)
    }
    return ! 0
},
$.validator.methods.isDifferent = function(value, element, param) {
    return $(param).val() === value ? !1 : !0
};
var sensitiveWord = ["bitch", "shit", "falun", "tianwang", "cdjp", "bignews", "boxun", "chinaliberal", "chinamz", "chinesenewsnet", "cnd", "creaders", "dafa", "dajiyuan", "dfdz", "dpp", "falu", "falundafa", "flg", "freechina", "freenet", "fuck", "GCD", "hongzhi", "hrichina", "huanet", "hypermart", "incest", "jiangdongriji", "lihongzhi", "making", "minghui", "minghuinews", "nacb", "naive", "nmis", "paper", "peacehall", "renminbao", "renmingbao", "rfa", "safeweb", "sex", "svdc", "taip", "tibetalk", "triangleboy", "UltraSurf", "unixbox", "ustibet", "voa", "wangce", "wstaiji", "xinsheng", "yuming", "zhengjian", "zhengjianwang", "zhenshanren", "zhuanfalun", "xxx", "anime", "censor", "hentai", "[hz]", "(hz)", "[av]", "(av)", "[sm]", "(sm)", "porn", "multimedia", "toolbar", "downloader", "女優", "小泽玛莉亚", "强歼", "乱交", "色友", "婊子", "蒲团", "美女", "女女", "喷尿", "绝版", "三級", "武腾兰", "凌辱", "暴干", "诱惑", "阴唇", "小泽圆", "插插", "坐交", "長瀨愛", "川島和津實", "草莓牛奶", "小澤園", "飯島愛", "星崎未來", "及川奈央", "朝河蘭", "夕樹舞子", "大澤惠", "金澤文子", "三浦愛佳", "伊東", "慰安妇", "女教師", "武藤蘭", "学生妹", "无毛", "猛插", "护士", "自拍", "A片", "A级", "喷精", "偷窥", "小穴", "大片", "被虐", "黄色", "被迫", "被逼", "强暴", "口技", "破处", "精液", "幼交", "狂干", "兽交", "群交", "叶子楣", "舒淇", "翁虹", "大陆", "露点", "露毛", "武藤兰", "饭岛爱", "波霸", "少儿不宜", "成人", "偷情", "叫床", "上床", "制服", "亚热", "援交", "走光", "情色", "肉欲", "美腿", "自摸", "18禁", "捆绑", "丝袜", "潮吹", "肛交", "黄片", "群射", "内射", "少妇", "卡通", "臭作", "薄格", "調教", "近親", "連發", "限制", "乱伦", "母子", "偷拍", "更衣", "無修正", "一本道", "1Pondo", "櫻井", "風花", "夜勤病栋", "菱恝", "虐待", "激情", "麻衣", "三级", "吐血", "三个代表", "一党", "多党", "民主", "专政", "行房", "自慰", "吹萧", "色狼", "胸罩", "内裤", "底裤", "私处", "爽死", "变态", "妹疼", "妹痛", "弟疼", "弟痛", "姐疼", "姐痛", "哥疼", "哥痛", "同房", "打炮", "造爱", "作爱", "做爱", "鸡巴", "阴茎", "阳具", "开苞", "肛门", "阴道", "阴蒂", "肉棍", "肉棒", "肉洞", "荡妇", "阴囊", "睾丸", "捅你", "捅我", "插我", "插你", "插她", "插他", "干你", "干她", "干他", "射精", "口交", "屁眼", "阴户", "阴门", "下体", "龟头", "阴毛", "避孕套", "你妈逼", "大鸡巴", "高潮", "政治", "大法", "弟子", "大纪元", "真善忍", "明慧", "洪志", "红志", "洪智", "红智", "法轮", "法论", "法沦", "法伦", "发轮", "发论", "发沦", "发伦", "轮功", "轮公", "轮攻", "沦功", "沦公", "沦攻", "论攻", "论功", "论公", "伦攻", "伦功", "伦公", "打倒", "民运", "六四", "台独", "王丹", "柴玲", "李鹏", "天安门", "江泽民", "朱容基", "朱镕基", "李长春", "李瑞环", "胡锦涛", "魏京生", "台湾独立", "藏独", "西藏独立", "疆独", "新疆独立", "警察", "民警", "公安", "邓小平", "大盖帽", "革命", "武警", "黑社会", "交警", "消防队", "刑警", "夜总会", "妈个", "公款", "首长", "书记", "坐台", "腐败", "城管", "暴动", "暴乱", "李远哲", "司法警官", "高干", "人大", "尉健行", "李岚清", "黄丽满", "于幼军", "文字狱", "宋祖英", "自焚", "骗局", "猫肉", "吸储", "张五常", "张丕林", "空难", "温家宝", "吴邦国", "曾庆红", "黄菊", "罗干", "吴官正", "贾庆林", "专制", "三個代表", "一黨", "多黨", "專政", "大紀元", "紅志", "紅智", "法輪", "法論", "法淪", "法倫", "發輪", "發論", "發淪", "發倫", "輪功", "輪公", "輪攻", "淪功", "淪公", "淪攻", "論攻", "論功", "論公", "倫攻", "倫功", "倫公", "民運", "台獨", "李鵬", "天安門", "江澤民", "朱鎔基", "李長春", "李瑞環", "胡錦濤", "臺灣獨立", "藏獨", "西藏獨立", "疆獨", "新疆獨立", "鄧小平", "大蓋帽", "黑社會", "消防隊", "夜總會", "媽個", "首長", "書記", "腐敗", "暴動", "暴亂", "李遠哲", "高幹", "李嵐清", "黃麗滿", "於幼軍", "文字獄", "騙局", "貓肉", "吸儲", "張五常", "張丕林", "空難", "溫家寶", "吳邦國", "曾慶紅", "黃菊", "羅幹", "賈慶林", "專制", "八九", "八老", "巴赫", "白立朴", "白梦", "白皮书", "保钓", "鲍戈", "鲍彤", "暴政", "北大三角地论坛", "北韩", "北京当局", "北京之春", "北美自由论坛", "博讯", "蔡崇国", "曹长青", "曹刚川", "常劲", "陈炳基", "陈军", "陈蒙", "陈破空", "陈希同", "陈小同", "陈宣良", "陈一谘", "陈总统", "程凯", "程铁军", "程真", "迟浩田", "持不同政见", "赤匪", "赤化", "春夏自由论坛", "达赖", "大参考", "大纪元新闻网", "大纪园", "大家论坛", "大史", "大史记", "大史纪", "大中国论坛", "大中华论坛", "大众真人真事", "戴相龙", "弹劾", "登辉", "邓笑贫", "迪里夏提", "地下教会", "地下刊物", "第四代", "电视流氓", "钓鱼岛", "丁关根", "丁元", "丁子霖", "东北独立", "东方红时空", "东方时空", "东南西北论谈", "东社", "东土耳其斯坦", "东西南北论坛", "动乱", "独裁", "独夫", "独立台湾会", "杜智富", "多维", "屙民", "俄国", "发愣", "发正念", "反封锁技术", "反腐败论坛", "反攻", "反共", "反人类", "反社会", "方励之", "方舟子", "飞扬论坛", "斐得勒", "费良勇", "分家在", "分裂", "粉饰太平", "风雨神州", "风雨神州论坛", "封从德", "封杀", "冯东海", "冯素英", "佛展千手法", "付申奇", "傅申奇", "傅志寰", "高官", "高文谦", "高薪养廉", "高瞻", "高自联", "戈扬", "鸽派", "歌功颂德", "蛤蟆", "个人崇拜", "工自联", "功法", "共产", "共党", "共匪", "共狗", "共军", "关卓中", "贯通两极法", "广闻", "郭伯雄", "郭罗基", "郭平", "郭岩华", "国家安全", "国家机密", "国军", "国贼", "韩东方", "韩联潮", "何德普", "何勇", "河殇", "红灯区", "红色恐怖", "宏法", "洪传", "洪吟", "洪哲胜", "胡紧掏", "胡锦滔", "胡锦淘", "胡景涛", "胡平", "胡总书记", "护法", "华建敏", "华通时事论坛", "华夏文摘", "华语世界论坛", "华岳时事论坛", "黄慈萍", "黄祸", "黄菊", "黄翔", "回民暴动", "悔过书", "鸡毛信文汇", "姬胜德", "积克馆", "基督", "贾廷安", "贾育台", "建国党", "江core", "江八点", "江流氓", "江罗", "江绵恒", "江青", "江戏子", "江则民", "江泽慧", "江贼", "江贼民", "江折民", "江猪", "江猪媳", "江主席", "姜春云", "将则民", "僵贼", "僵贼民", "讲法", "酱猪媳", "交班", "教养院", "接班", "揭批书", "金尧如", "锦涛", "禁看", "经文", "开放杂志", "看中国", "抗议", "邝锦文", "劳动教养所", "劳改", "劳教", "老江", "老毛", "黎安友", "李大师", "李登辉", "李红痔", "李宏志", "李洪宽", "李继耐", "李兰菊", "李老师", "李录", "李禄", "李少民", "李淑娴", "李旺阳", "李文斌", "李小朋", "李小鹏", "李月月鸟", "李志绥", "李总理", "李总统", "连胜德", "联总", "廉政大论坛", "炼功", "梁光烈", "梁擎墩", "两岸关系", "两岸三地论坛", "两个中国", "两会", "两会报道", "两会新闻", "廖锡龙", "林保华", "林长盛", "林樵清", "林慎立", "凌锋", "刘宾深", "刘宾雁", "刘刚", "刘国凯", "刘华清", "刘俊国", "刘凯中", "刘千石", "刘青", "刘山青", "刘士贤", "刘文胜", "刘晓波", "刘晓竹", "刘永川", "流亡", "龙虎豹", "陆委会", "吕京花", "吕秀莲", "抡功", "轮大", "罗礼诗", "马大维", "马良骏", "马三家", "马时敏", "卖国", "毛厕洞", "毛贼东", "美国参考", "美国之音", "蒙独", "蒙古独立", "密穴", "绵恒", "民国", "民进党", "民联", "民意", "民意论坛", "民阵", "民猪", "民主墙", "民族矛盾", "莫伟强", "木犀地", "木子论坛", "南大自由论坛", "闹事", "倪育贤", "你说我说论坛", "潘国平", "泡沫经济", "迫害", "祁建", "齐墨", "钱达", "钱国梁", "钱其琛", "抢粮记", "乔石", "亲美", "钦本立", "秦晋", "轻舟快讯", "情妇", "庆红", "全国两会", "热比娅", "热站政论网", "人民报", "人民内情真相", "人民真实", "人民之声论坛", "人权", "瑞士金融大学", "善恶有报", "上海帮", "上海孤儿院", "邵家健", "神通加持法", "沈彤", "升天", "盛华仁", "盛雪", "师父", "石戈", "时代论坛", "时事论坛", "世界经济导报", "事实独立", "双十节", "水扁", "税力", "司马晋", "司马璐", "司徒华", "斯诺", "四川独立", "宋平", "宋书元", "苏绍智", "苏晓康", "台盟", "台湾狗", "台湾建国运动组织", "台湾青年独立联盟", "台湾政论区", "台湾自由联盟", "太子党", "汤光中", "唐柏桥", "唐捷", "滕文生", "天怒", "天葬", "童屹", "统独", "统独论坛", "统战", "屠杀", "外交论坛", "外交与方略", "万润南", "万维读者论坛", "万晓东", "汪岷", "王宝森", "王炳章", "王策", "王超华", "王辅臣", "王刚", "王涵万", "王沪宁", "王军涛", "王力雄", "王瑞林", "王润生", "王若望", "王希哲", "王秀丽", "王冶坪", "网特", "魏新生", "温元凯", "文革", "无界浏览器", "吴百益", "吴方城", "吴弘达", "吴宏达", "吴仁华", "吴学灿", "吴学璨", "吾尔开希", "五不", "伍凡", "西藏", "洗脑", "项怀诚", "项小吉", "小参考", "肖强", "邪恶", "谢长廷", "谢选骏", "谢中之", "辛灏年", "新观察论坛", "新华举报", "新华内情", "新华通论坛", "新生网", "新闻封锁", "新语丝", "信用危机", "邢铮", "熊炎", "熊焱", "修炼", "徐邦秦", "徐才厚", "徐匡迪", "徐水良", "许家屯", "薛伟", "学潮", "学联", "学习班", "学运", "学自联", "雪山狮子", "严家其", "严家祺", "阎明复", "颜射", "央视内部晚会", "杨怀安", "杨建利", "杨巍", "杨月清", "杨周", "姚月谦", "夜话紫禁城", "一中一台", "义解", "亦凡", "异见人士", "异议人士", "易丹轩", "易志熹", "尹庆民", "由喜贵", "游行", "幼齿", "于浩成", "余英时", "舆论", "舆论反制", "宇明网", "圆满", "远志明", "岳武", "在十月", "则民", "择民", "泽民", "贼民", "曾培炎", "张伯笠", "张钢", "张宏堡", "张林", "张万年", "张伟国", "张昭富", "张志清", "赵海青", "赵南", "赵品潞", "赵晓微", "赵紫阳", "哲民", "真相", "真象", "镇压", "争鸣论坛", "正见网", "正义党论坛", "郑义", "包夜", "冰火", "插B", "操B", "处女", "打飞机", "风骚", "黄色电影", "激情视频", "叫春", "狂插", "狂操", "狂搞", "露乳", "裸聊", "裸体", "屁股", "强奸", "三级片", "色情", "脱光", "脱衣", "性爱", "性感", "性高潮", "性交", "胸部", "艳舞", "一夜情", "欲望", "操你", "你他妈", "傻逼", "傻B", "TMD", "TNND", "TND", "法轮功", "江氏", "李洪志", "新唐人", "淫靡", "淫水", "六四事件", "迷药", "迷昏药", "窃听器", "六合彩", "买卖枪支", "退党", "三唑仑", "麻醉药", "麻醉乙醚", "短信群发器", "帝国之梦", "毛一鲜", "色情服务", "对日强硬", "出售枪支", "摇头丸", "西藏天葬", "鬼村", "军长发威", "PK黑社会", "枪决女犯", "投毒杀人", "强硬发言", "出售假币", "监听王", "昏药", "侦探设备", "麻醉钢枪", "反华", "官商勾结", "自杀指南", "自杀手册", "佳静安定片", "蒙汗药粉", "古方迷香", "强效失意药", "迷奸药", "透视眼镜", "远程偷拍", "自制手枪", "子女任职名单", "激情小电影", "黄色小电影", "色情小电影", "天鹅之旅", "盘古乐队", "高校暴乱", "高校群体事件", "大学骚乱", "高校骚乱", "催情药", "拍肩神药", "春药", "窃听器材", "身份证生成器", "枪决现场", "出售手枪", "麻醉枪", "办理证件", "办理文凭", "疆独藏独", "高干子弟", "高干子女", "枪支弹药", "血腥图片", "反政府", "禁书", "特码", "成人片", "成人电影", "换妻", "发票", "增值税", "迷幻药", "隐形", "宏志", "杀死", "发抡", "拉登", "拉丹", "法抡", "法囵", "法仑", "法纶", "发仑", "发囵", "国研新闻邮件", "自由运动", "法轮大法", "淫秽", "E周刊", "龙卷风", "正法", "三陪", "嫖娼", "静坐", "政变", "造反", "独立", "发轮功", "功友", "人民大众时事参考", "示威", "罢工", "大法弟子", "印尼伊斯兰祈祷团", "中俄边界新约", "政治运动", "压迫", "非典", "共产党", "反革命", "十六大", "江独裁", "台湾", "东突厥斯坦伊斯兰运动", "一边一国", "回民", "中华民国", "政治风波", "突厥斯坦", "简鸿章", "联总之声传单", "人民报讯", "东突", "人民真实报道", "教徒", "推翻", "小灵通", "操你奶奶", "操你妈", "falun", "IP17908", "falong", "陈水扁", "主席", "改革", "他妈的", "人民真实报导", "开放", "中俄", "边界新约", "（诽闻）", "印尼依斯兰祈祷团", "东突厥斯坦依斯兰运动", "本拉登", "维吾尔自由运动", "国际地质科学联合会", "中国民主正义党", "www.cdjp.org", "民主中国", "www.chinamz.org", "中国民主同盟", "支联会", "天安门母亲", "张戎", "西藏流亡政府", "邓力群", "龙新民", "www.bignews.org", "www.boxun.com", "也就是博讯", "www.cnd.org", "www.chinesenewsnet.com", "纪元", "www.dajiyuan.com", "大纪元时报", "自由亚洲", "www.rfa.org", "www.renminbao.com", "维基百科", "www.lvmaque.com", "根敦.确吉尼玛", "根敦.确吉", "确吉尼玛", "西藏论坛", "www.tibetalk.com", "破网软件", "无界", "自由门", "花园网", "我的奋斗", "lvmaque", "绿麻雀", "安投融", "毛泽东", "王博", "谷云", "赵春霞", "王晓文", "孟繁春", "习近平", "李克强", "张德江", "俞正声", "刘云山", "王岐山", "张高丽", "彭丽媛"];