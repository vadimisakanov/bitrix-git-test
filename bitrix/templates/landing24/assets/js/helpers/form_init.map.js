{"version":3,"sources":["form_init.js"],"names":["BX","addCustomEvent","event","selector","makeRelativeSelector","block","querySelectorAll","length","window","id","LandingForm","initForm","currentForm","attr","data","replace","dataFormId","onFormRemove","dataAttributeShowHeader","Object","keys","formOptions","css","content","matchShowHeader","onFormReload","dataAttributeUseStyle","createFormOptions","document","this","dataAttributeDomain","dataAttributePrefix","hideHeaderString","hideBitrixLogoString","paddingFixesString","domainNode","findChild","attribute","isFormChosen","domain","initFormLoader","createFullDomain","styleParams","wrapper-padding","params","bg","bg-content","bg-block","main-bg","main-border-color","main-font-family","main-font-color","main-font-color-hover","main-font-weight","second-font-color","icon-font-color","button-font-color","header-font-size","header-font-weight","header-text-font-size","label-font-size","border-block","input-bg","input-box-shadow","input-select-bg","input-border","input-border-radius","input-border-color","input-border-hover","agreement-label-font-size","styles","selectors",".crm-webform-wrapper, .content-wrap","body.crm-webform-iframe",".content",".crm-webform-block",".crm-webform-header-container",".crm-webform-header-container h2",".crm-webform-inner-header",".crm-webform-mini-cart-title, .crm-webform-mini-cart-services-container",".crm-webform-header",".crm-webform-label","button.crm-webform-submit-button, .crm-webform-file-upload .crm-webform-file-button",".crm-webform-label-content, .crm-webform-file-text-field",".crm-webform-input-label",".crm-webform-input, .crm-webform-file-text-field",".crm-webform-icon",".crm-webform-desktop-font-style a",".crm-webform-desktop-font-style a:hover",".crm-webform-input option",".crm-webform-active .crm-webform-input, .crm-webform-active mark, .crm-webform-input:hover",".crm-webform-checkbox-container:hover i",".crm-webform-checkbox-name",".crm-webform-input+i:after",".crm-webform-agreement-modifier .crm-webform-checkbox-name","formParams","prototype","b24Forms","i","c","w","d","u","b","arguments","ref","forms","push","s","createElement","r","Date","async","src","h","getElementsByTagName","parentNode","insertBefore","createNoFormMessage","createFormParams","proxy","onFormFrameLoad","addFormInLoader","Bitrix24FormLoader","init","preLoad","Landing","getMode","formContainer","querySelector","messageNode","create","tag","props","className","html","adjust","children","formCode","formParts","split","lang","message","sec","type","node","b24form","removeFormFromLoader","Bitrix24FormObject","forEach","form","uniqueLoadId","ie","postMessage","messageDomain","match","frameParameters","options","iframe","contentWindow","JSON","stringify","unload","fullDomain","isUsingCustomStyle","cssContent","createFormOptionsCss","cssFiles","createFormOptionsCssFiles","file","createHideBitrixLabelCss","createPaddingFixesCss","files","readFormStyles","cssString","cssStringCurrent","style","delegate","param","value","string","Array"],"mappings":"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","file":""}