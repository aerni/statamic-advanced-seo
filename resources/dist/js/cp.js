(()=>{var e,t={481:()=>{Statamic.booted((function(){Statamic.$store.dispatch("publish/advancedSeo/fetchConditions")})),Statamic.$store.watch((function(e){var t,i;return null===(t=e.publish)||void 0===t||null===(i=t.base)||void 0===i?void 0:i.site}),(function(){Statamic.$store.dispatch("publish/advancedSeo/fetchConditions")})),Statamic.$conditions.add("showSitemapFields",(function(e){var t;return null===(t=e.store.state.publish.advancedSeo.conditions)||void 0===t?void 0:t.showSitemapFields})),Statamic.$conditions.add("showSocialImagesGeneratorFields",(function(e){var t;return null===(t=e.store.state.publish.advancedSeo.conditions)||void 0===t?void 0:t.showSocialImagesGeneratorFields}))},433:(e,t,i)=>{"use strict";function n(e,t){var i=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),i.push.apply(i,n)}return i}function a(e){for(var t=1;t<arguments.length;t++){var i=null!=arguments[t]?arguments[t]:{};t%2?n(Object(i),!0).forEach((function(t){s(e,t,i[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(i)):n(Object(i)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(i,t))}))}return e}function s(e,t,i){return t in e?Object.defineProperty(e,t,{value:i,enumerable:!0,configurable:!0,writable:!0}):e[t]=i,e}const o={props:{publishContainer:String,initialReference:String,initialFieldset:Object,initialValues:Object,initialMeta:Object,initialTitle:String,initialLocalizations:Array,initialLocalizedFields:Array,initialHasOrigin:Boolean,initialOriginValues:Object,initialOriginMeta:Object,initialSite:String,breadcrumbs:Array,initialActions:Object,method:String,isCreating:Boolean,initialReadOnly:Boolean,initialIsRoot:Boolean,contentType:String},data:function(){return{actions:this.initialActions,saving:!1,localizing:!1,fieldset:this.initialFieldset,title:this.initialTitle,values:_.clone(this.initialValues),meta:_.clone(this.initialMeta),localizations:_.clone(this.initialLocalizations),localizedFields:this.initialLocalizedFields,hasOrigin:this.initialHasOrigin,originValues:this.initialOriginValues||{},originMeta:this.initialOriginMeta||{},site:this.initialSite,error:null,errors:{},isRoot:this.initialIsRoot,readOnly:this.initialReadOnly}},computed:{shouldShowSites:function(){return this.localizations.length>1},hasErrors:function(){return this.error||Object.keys(this.errors).length},somethingIsLoading:function(){return!this.$progress.isComplete()},canSave:function(){return!this.readOnly&&this.isDirty&&!this.somethingIsLoading},isBase:function(){return"base"===this.publishContainer},isDirty:function(){return this.$dirty.has(this.publishContainer)},activeLocalization:function(){return _.findWhere(this.localizations,{active:!0})},originLocalization:function(){return _.findWhere(this.localizations,{origin:!0})},computedBreadcrumbs:function(){return{url:this.breadcrumbs[0].url,text:this.breadcrumbs[0].text}}},watch:{saving:function(e){this.$progress.loading("".concat(this.publishContainer,"-defaults-publish-form"),e)}},methods:{clearErrors:function(){this.error=null,this.errors={}},save:function(){var e=this;if(this.canSave){this.saving=!0,this.clearErrors();var t=a(a({},this.values),{blueprint:this.fieldset.handle,_localized:this.localizedFields});this.$axios[this.method](this.actions.save,t).then((function(t){e.saving=!1,e.isCreating||e.$toast.success(__("Saved")),e.$refs.container.saved(),e.$nextTick((function(){return e.$emit("saved",t)}))})).catch((function(t){return e.handleAxiosError(t)}))}},handleAxiosError:function(e){if(this.saving=!1,e.response&&422===e.response.status){var t=e.response.data,i=t.message,n=t.errors;this.error=i,this.errors=n,this.$toast.error(i)}else this.$toast.error(__("Something went wrong"))},localizationSelected:function(e){var t=this;e.active||this.isDirty&&!confirm(__("Are you sure? Unsaved changes will be lost."))||(this.$dirty.remove(this.publishContainer),this.localizing=e.handle,this.isBase&&window.history.replaceState({},"",e.url),this.$axios.get(e.url).then((function(i){var n=i.data;t.values=n.values,t.originValues=n.originValues,t.originMeta=n.originMeta,t.meta=n.meta,t.localizations=n.localizations,t.localizedFields=n.localizedFields,t.hasOrigin=n.hasOrigin,t.actions=n.actions,t.fieldset=n.blueprint,t.isRoot=n.isRoot,t.site=e.handle,t.localizing=!1,t.$nextTick((function(){return t.$refs.container.clearDirtyState()}))})))},setFieldValue:function(e,t){this.hasOrigin&&this.desyncField(e),this.$refs.container.setFieldValue(e,t)},syncField:function(e){confirm(__("Are you sure? This field's value will be replaced by the value in the original entry."))&&(this.localizedFields=this.localizedFields.filter((function(t){return t!==e})),this.$refs.container.setFieldValue(e,this.originValues[e]),this.meta[e]=this.originMeta[e])},desyncField:function(e){this.localizedFields.includes(e)||this.localizedFields.push(e),this.$refs.container.dirty()}},mounted:function(){var e=this;this.$keys.bindGlobal(["mod+s"],(function(t){t.preventDefault(),e.save()}))},created:function(){window.history.replaceState({},document.title,document.location.href.replace("created=true",""))}};var r=i(379),l=i.n(r),u=i(362),c={insert:"head",singleton:!1};l()(u.Z,c);u.Z.locals;function d(e,t,i,n,a,s,o,r){var l,u="function"==typeof e?e.options:e;if(t&&(u.render=t,u.staticRenderFns=i,u._compiled=!0),n&&(u.functional=!0),s&&(u._scopeId="data-v-"+s),o?(l=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),a&&a.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(o)},u._ssrRegister=l):a&&(l=r?function(){a.call(this,(u.functional?this.parent:this).$root.$options.shadowRoot)}:a),l)if(u.functional){u._injectStyles=l;var c=u.render;u.render=function(e,t){return l.call(t),c(e,t)}}else{var d=u.beforeCreate;u.beforeCreate=d?[].concat(d,l):[l]}return{exports:e,options:u}}const f=d(o,(function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",{staticClass:"remove-border-bottom"},[i("header",{staticClass:"mb-3"},[i("breadcrumb",{attrs:{url:e.computedBreadcrumbs.url,title:e.computedBreadcrumbs.text}}),e._v(" "),i("div",{staticClass:"flex items-center"},[i("h1",{staticClass:"flex-1",domProps:{textContent:e._s(e.title)}}),e._v(" "),e.readOnly?i("div",{staticClass:"flex pt-px text-2xs text-grey-60"},[i("svg-icon",{staticClass:"w-4 mr-sm -mt-sm",attrs:{name:"lock"}}),e._v(" "+e._s(e.__("Read Only"))+"\n            ")],1):e._e(),e._v(" "),e.readOnly?e._e():i("button",{staticClass:"ml-2 btn-primary min-w-100",class:{"opacity-25":!e.canSave},attrs:{disabled:!e.canSave},domProps:{textContent:e._s(e.__("Save"))},on:{click:function(t){return t.preventDefault(),e.save.apply(null,arguments)}}})])],1),e._v(" "),i("publish-container",{ref:"container",attrs:{name:e.publishContainer,blueprint:e.fieldset,values:e.values,reference:e.initialReference,meta:e.meta,errors:e.errors,site:e.site,"localized-fields":e.localizedFields,"is-root":e.isRoot},on:{updated:function(t){e.values=t}},scopedSlots:e._u([{key:"default",fn:function(t){var n=t.container,a=t.components,s=t.setFieldMeta;return i("div",{},[e._l(a,(function(t){return i(t.name,e._b({key:t.name,tag:"component",attrs:{container:n}},"component",t.props,!1))})),e._v(" "),i("publish-sections",{attrs:{"read-only":e.readOnly,syncable:e.hasOrigin,"can-toggle-labels":!0,"enable-sidebar":e.shouldShowSites},on:{updated:e.setFieldValue,"meta-updated":s,synced:e.syncField,desynced:e.desyncField,focus:function(e){return n.$emit("focus",e)},blur:function(e){return n.$emit("blur",e)}},scopedSlots:e._u([{key:"actions",fn:function(t){t.shouldShowSidebar;return[e.shouldShowSites?i("div",{staticClass:"p-2"},[i("label",{staticClass:"mb-1 font-medium publish-field-label",domProps:{textContent:e._s(e.__("Sites"))}}),e._v(" "),e._l(e.localizations,(function(t){return i("div",{key:t.handle,staticClass:"flex items-center px-2 py-1 -mx-2 text-sm cursor-pointer",class:t.active?"bg-blue-100":"hover:bg-grey-20",on:{click:function(i){return e.localizationSelected(t)}}},[i("div",{staticClass:"flex items-center flex-1"},[e._v("\n                                "+e._s(t.name)+"\n                                "),e.localizing===t.handle?i("loading-graphic",{staticClass:"flex items-center ml-1",staticStyle:{"padding-bottom":"0.05em"},attrs:{size:14,text:""}}):e._e()],1),e._v(" "),t.origin?i("div",{staticClass:"badge-sm bg-orange",domProps:{textContent:e._s(e.__("Origin"))}}):e._e(),e._v(" "),t.active?i("div",{staticClass:"badge-sm bg-blue",domProps:{textContent:e._s(e.__("Active"))}}):e._e(),e._v(" "),!t.root||t.origin||t.active?e._e():i("div",{staticClass:"badge-sm bg-purple",domProps:{textContent:e._s(e.__("Root"))}})])}))],2):e._e()]}}],null,!0)})],2)}}])})],1)}),[],!1,null,"5a3d930a",null).exports;const h=d({mixins:[Fieldtype],data:function(){return{image:this.meta.image}},mounted:function(){var e=this;Statamic.$hooks.on("entry.saved",(function(t){e.image&&(e.image="".concat(e.image,"?reload")),t()}))},watch:{"$store.state.publish.base.site":function(){this.image=this.meta.image}},computed:{exists:function(){var e=new XMLHttpRequest;return e.open("HEAD",this.image,!1),e.send(),404!=e.status}}},(function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",[this.exists?i("div",[i("img",{staticClass:"rounded-md",attrs:{src:this.image}})]):i("div",{staticClass:"p-3 text-center border rounded",staticStyle:{"border-color":"#c4ccd4","background-color":"#fafcff"}},[i("small",{staticClass:"mb-0 help-block"},[e._v(e._s(this.meta.message))])])])}),[],!1,null,null,null).exports;function p(e){return p="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},p(e)}const m={mixins:[Fieldtype],data:function(){return{autoBindChangeWatcher:!1,changeWatcherWatchDeep:!1,customValue:null}},computed:{fieldSource:function(){return this.value.source},fieldDefault:function(){return this.meta.default},fieldValue:function(){return this.value.value},fieldComponent:function(){var e=this.config.field.type;return(this.config.field.component||e).replace(".","-")+"-fieldtype"},fieldConfig:function(){return this.config.field},fieldMeta:function(){return this.meta.meta},autoFieldDisplay:function(){var e=this.store.blueprint.sections.flatMap((function(e){return e.fields}));return _.find(e,{handle:this.autoFieldHandle}).display},autoFieldValue:function(){var e=this.store.values[this.autoFieldHandle];return"object"===p(e)&&null!==e?e.value:e},autoFieldHandle:function(){return this.config.auto},fieldIsSynced:function(){return this.$parent.$parent.isSynced},sourceOptions:function(){var e=this,t=[{label:__("advanced-seo::messages.field_sources.default"),value:"default"},{label:__("advanced-seo::messages.field_sources.custom"),value:"custom"}];return this.autoFieldHandle&&t.unshift({label:__("advanced-seo::messages.field_sources.auto"),value:"auto"}),this.config.options?t.filter((function(t){return e.config.options.includes(t.value)})):t},site:function(){return this.store.site},store:function(){return this.$store.state.publish.base}},watch:{autoFieldValue:function(){this.updateAutoFieldValue()},fieldIsSynced:function(e){!0===e&&this.updateAutoFieldValue()},fieldSource:function(e){"auto"===e&&this.updateFieldValue(this.autoFieldValue),"default"===e&&this.updateFieldValue(this.fieldDefault),"custom"===e&&this.updateFieldValue(null===this.customValue?this.fieldDefault:this.customValue)},site:function(){this.customValue=null,this.updateAutoFieldValue()}},mounted:function(){this.updateAutoFieldValue(),"custom"===this.fieldSource&&this.updateCustomValue(this.fieldValue)},methods:{updateAutoFieldValue:function(){"auto"===this.fieldSource&&(this.value.value=this.autoFieldValue)},updateFieldSource:function(e){this.fieldSource!==e&&(this.value.source=e)},updateFieldValue:function(e){this.value.value=e,this.update(this.value)},updateCustomFieldValue:function(e){this.updateCustomValue(e),this.updateFieldValue(e)},updateCustomValue:function(e){this.customValue=e},updateFieldMeta:function(e){this.meta.meta=e||this.fieldMeta}}};const v=d(m,(function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",{staticClass:"flex flex-col seo-mt-4"},[i("div",{staticClass:"self-start button-group-fieldtype-wrapper"},[i("div",{staticClass:"seo-h-auto btn-group source-btn-group"},e._l(e.sourceOptions,(function(t,n){return i("button",{key:n,ref:"button",refInFor:!0,staticClass:"btn seo-h-auto seo-text-xs",class:{active:e.fieldSource===t.value},staticStyle:{padding:"1px 5px"},attrs:{name:e.name,value:t.value,disabled:e.isReadOnly},domProps:{textContent:e._s(t.label||t.value)},on:{click:function(t){return e.updateFieldSource(t.target.value)}}})})),0)]),e._v(" "),i("div",{staticClass:"seo-mt-2.5"},["custom"===e.fieldSource?i("div",[i(e.fieldComponent,{tag:"component",attrs:{name:e.name,config:e.fieldConfig,meta:e.fieldMeta,value:e.fieldValue,"read-only":e.isReadOnly,handle:"source_value"},on:{"meta-updated":e.updateFieldMeta,input:e.updateCustomFieldValue}})],1):i("div",[i(e.fieldComponent,{tag:"component",attrs:{name:e.name,config:e.fieldConfig,meta:e.fieldMeta,value:e.fieldValue,"read-only":"true",handle:"source_value"}}),e._v(" "),i("div",{staticClass:"mt-1 help-block"},["auto"===e.fieldSource?i("span",{domProps:{innerHTML:e._s(e.__("advanced-seo::messages.field_source_description.auto",{title:this.autoFieldDisplay,handle:this.autoFieldHandle}))}}):i("span",{domProps:{innerHTML:e._s(e.__("advanced-seo::messages.field_source_description.defaults",{title:this.meta.title}))}})])],1)])])}),[],!1,null,null,null).exports;Statamic.booting((function(){Statamic.component("defaults-publish-form",f),Statamic.component("social_image-fieldtype",h),Statamic.component("seo_source-fieldtype",v)}));i(481),i(169)},169:()=>{Statamic.$store.registerModule(["publish","advancedSeo"],{namespaced:!0,state:{conditions:null},getters:{conditions:function(e){return e.conditions}},actions:{fetchConditions:function(e){var t,i,n,a=e.commit,s=null===(t=Statamic.$store.state.publish)||void 0===t||null===(i=t.base)||void 0===i||null===(n=i.values)||void 0===n?void 0:n.id;if(s)return Statamic.$request.post("/!/advanced-seo/conditions",{id:s,site:Statamic.$store.state.publish.base.site}).then((function(e){return a("setConditions",e.data)})).catch((function(e){console.log(e)}))}},mutations:{setConditions:function(e,t){e.conditions=t}}})},362:(e,t,i)=>{"use strict";i.d(t,{Z:()=>s});var n=i(519),a=i.n(n)()((function(e){return e[1]}));a.push([e.id,".remove-border-bottom[data-v-5a3d930a] .publish-sidebar .publish-section-actions{border-bottom-width:0}",""]);const s=a},519:e=>{"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var i=e(t);return t[2]?"@media ".concat(t[2]," {").concat(i,"}"):i})).join("")},t.i=function(e,i,n){"string"==typeof e&&(e=[[null,e,""]]);var a={};if(n)for(var s=0;s<this.length;s++){var o=this[s][0];null!=o&&(a[o]=!0)}for(var r=0;r<e.length;r++){var l=[].concat(e[r]);n&&a[l[0]]||(i&&(l[2]?l[2]="".concat(i," and ").concat(l[2]):l[2]=i),t.push(l))}},t}},503:()=>{},379:(e,t,i)=>{"use strict";var n,a=function(){return void 0===n&&(n=Boolean(window&&document&&document.all&&!window.atob)),n},s=function(){var e={};return function(t){if(void 0===e[t]){var i=document.querySelector(t);if(window.HTMLIFrameElement&&i instanceof window.HTMLIFrameElement)try{i=i.contentDocument.head}catch(e){i=null}e[t]=i}return e[t]}}(),o=[];function r(e){for(var t=-1,i=0;i<o.length;i++)if(o[i].identifier===e){t=i;break}return t}function l(e,t){for(var i={},n=[],a=0;a<e.length;a++){var s=e[a],l=t.base?s[0]+t.base:s[0],u=i[l]||0,c="".concat(l," ").concat(u);i[l]=u+1;var d=r(c),f={css:s[1],media:s[2],sourceMap:s[3]};-1!==d?(o[d].references++,o[d].updater(f)):o.push({identifier:c,updater:v(f,t),references:1}),n.push(c)}return n}function u(e){var t=document.createElement("style"),n=e.attributes||{};if(void 0===n.nonce){var a=i.nc;a&&(n.nonce=a)}if(Object.keys(n).forEach((function(e){t.setAttribute(e,n[e])})),"function"==typeof e.insert)e.insert(t);else{var o=s(e.insert||"head");if(!o)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");o.appendChild(t)}return t}var c,d=(c=[],function(e,t){return c[e]=t,c.filter(Boolean).join("\n")});function f(e,t,i,n){var a=i?"":n.media?"@media ".concat(n.media," {").concat(n.css,"}"):n.css;if(e.styleSheet)e.styleSheet.cssText=d(t,a);else{var s=document.createTextNode(a),o=e.childNodes;o[t]&&e.removeChild(o[t]),o.length?e.insertBefore(s,o[t]):e.appendChild(s)}}function h(e,t,i){var n=i.css,a=i.media,s=i.sourceMap;if(a?e.setAttribute("media",a):e.removeAttribute("media"),s&&"undefined"!=typeof btoa&&(n+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(s))))," */")),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}var p=null,m=0;function v(e,t){var i,n,a;if(t.singleton){var s=m++;i=p||(p=u(t)),n=f.bind(null,i,s,!1),a=f.bind(null,i,s,!0)}else i=u(t),n=h.bind(null,i,t),a=function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(i)};return n(e),function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap)return;n(e=t)}else a()}}e.exports=function(e,t){(t=t||{}).singleton||"boolean"==typeof t.singleton||(t.singleton=a());var i=l(e=e||[],t);return function(e){if(e=e||[],"[object Array]"===Object.prototype.toString.call(e)){for(var n=0;n<i.length;n++){var a=r(i[n]);o[a].references--}for(var s=l(e,t),u=0;u<i.length;u++){var c=r(i[u]);0===o[c].references&&(o[c].updater(),o.splice(c,1))}i=s}}}}},i={};function n(e){var a=i[e];if(void 0!==a)return a.exports;var s=i[e]={id:e,exports:{}};return t[e](s,s.exports,n),s.exports}n.m=t,e=[],n.O=(t,i,a,s)=>{if(!i){var o=1/0;for(c=0;c<e.length;c++){for(var[i,a,s]=e[c],r=!0,l=0;l<i.length;l++)(!1&s||o>=s)&&Object.keys(n.O).every((e=>n.O[e](i[l])))?i.splice(l--,1):(r=!1,s<o&&(o=s));if(r){e.splice(c--,1);var u=a();void 0!==u&&(t=u)}}return t}s=s||0;for(var c=e.length;c>0&&e[c-1][2]>s;c--)e[c]=e[c-1];e[c]=[i,a,s]},n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var i in t)n.o(t,i)&&!n.o(e,i)&&Object.defineProperty(e,i,{enumerable:!0,get:t[i]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={641:0,30:0};n.O.j=t=>0===e[t];var t=(t,i)=>{var a,s,[o,r,l]=i,u=0;if(o.some((t=>0!==e[t]))){for(a in r)n.o(r,a)&&(n.m[a]=r[a]);if(l)var c=l(n)}for(t&&t(i);u<o.length;u++)s=o[u],n.o(e,s)&&e[s]&&e[s][0](),e[s]=0;return n.O(c)},i=self.webpackChunk=self.webpackChunk||[];i.forEach(t.bind(null,0)),i.push=t.bind(null,i.push.bind(i))})(),n.O(void 0,[30],(()=>n(433)));var a=n.O(void 0,[30],(()=>n(503)));a=n.O(a)})();