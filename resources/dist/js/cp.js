/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vendor_statamic_cms_resources_js_components_SiteSelector_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../vendor/statamic/cms/resources/js/components/SiteSelector.vue */ "./vendor/statamic/cms/resources/js/components/SiteSelector.vue");
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  components: {
    SiteSelector: _vendor_statamic_cms_resources_js_components_SiteSelector_vue__WEBPACK_IMPORTED_MODULE_0__.default
  },
  props: {
    publishContainer: String,
    initialReference: String,
    initialFieldset: Object,
    initialValues: Object,
    initialMeta: Object,
    initialTitle: String,
    initialHandle: String,
    initialBlueprintHandle: String,
    initialLocalizations: Array,
    initialLocalizedFields: Array,
    initialHasOrigin: Boolean,
    initialOriginValues: Object,
    initialOriginMeta: Object,
    initialSite: String,
    defaultsUrl: String,
    defaultsTitle: String,
    initialActions: Object,
    method: String,
    isCreating: Boolean,
    initialReadOnly: Boolean,
    initialIsRoot: Boolean,
    canEdit: Boolean
  },
  data: function data() {
    return {
      actions: this.initialActions,
      saving: false,
      localizing: false,
      fieldset: this.initialFieldset,
      title: this.initialTitle,
      values: _.clone(this.initialValues),
      meta: _.clone(this.initialMeta),
      localizations: _.clone(this.initialLocalizations),
      localizedFields: this.initialLocalizedFields,
      hasOrigin: this.initialHasOrigin,
      originValues: this.initialOriginValues || {},
      originMeta: this.initialOriginMeta || {},
      site: this.initialSite,
      error: null,
      errors: {},
      isRoot: this.initialIsRoot
    };
  },
  computed: {
    hasErrors: function hasErrors() {
      return this.error || Object.keys(this.errors).length;
    },
    somethingIsLoading: function somethingIsLoading() {
      return !this.$progress.isComplete();
    },
    canSave: function canSave() {
      return this.canEdit && this.isDirty && !this.somethingIsLoading;
    },
    isBase: function isBase() {
      return this.publishContainer === 'base';
    },
    isDirty: function isDirty() {
      return this.$dirty.has(this.publishContainer);
    },
    activeLocalization: function activeLocalization() {
      return _.findWhere(this.localizations, {
        active: true
      });
    },
    originLocalization: function originLocalization() {
      return _.findWhere(this.localizations, {
        origin: true
      });
    }
  },
  watch: {
    saving: function saving(_saving) {
      this.$progress.loading("".concat(this.publishContainer, "-defaults-publish-form"), _saving);
    }
  },
  methods: {
    clearErrors: function clearErrors() {
      this.error = null;
      this.errors = {};
    },
    save: function save() {
      var _this = this;

      if (!this.canSave) return;
      this.saving = true;
      this.clearErrors();

      var payload = _objectSpread(_objectSpread({}, this.values), {
        blueprint: this.fieldset.handle,
        _localized: this.localizedFields
      });

      this.$axios[this.method](this.actions.save, payload).then(function (response) {
        _this.saving = false;
        if (!_this.isCreating) _this.$toast.success(__('Saved'));

        _this.$refs.container.saved();

        _this.$nextTick(function () {
          return _this.$emit('saved', response);
        });
      })["catch"](function (e) {
        return _this.handleAxiosError(e);
      });
    },
    handleAxiosError: function handleAxiosError(e) {
      this.saving = false;

      if (e.response && e.response.status === 422) {
        var _e$response$data = e.response.data,
            message = _e$response$data.message,
            errors = _e$response$data.errors;
        this.error = message;
        this.errors = errors;
        this.$toast.error(message);
      } else {
        this.$toast.error(__('Something went wrong'));
      }
    },
    localizationSelected: function localizationSelected(localization) {
      var _this2 = this;

      if (localization.active) return;

      if (this.isDirty) {
        if (!confirm(__('Are you sure? Unsaved changes will be lost.'))) {
          return;
        }
      }

      this.localizing = localization.handle;

      if (this.publishContainer === 'base') {
        window.history.replaceState({}, '', localization.url);
      }

      this.$axios.get(localization.url).then(function (response) {
        var data = response.data;
        _this2.values = data.values;
        _this2.originValues = data.originValues;
        _this2.meta = data.meta;
        _this2.localizations = data.localizations;
        _this2.localizedFields = data.localizedFields;
        _this2.hasOrigin = data.hasOrigin;
        _this2.actions = data.actions;
        _this2.fieldset = data.blueprint;
        _this2.isRoot = data.isRoot;
        _this2.site = localization.handle;
        _this2.localizing = false;

        _this2.$nextTick(function () {
          return _this2.$refs.container.clearDirtyState();
        });
      });
    },
    localizationStatusText: function localizationStatusText(localization) {
      return localization.exists ? 'This global set exists in this site.' : 'This global set does not exist for this site.';
    },
    setFieldValue: function setFieldValue(handle, value) {
      if (this.hasOrigin) this.desyncField(handle);
      this.$refs.container.setFieldValue(handle, value);
    },
    syncField: function syncField(handle) {
      if (!confirm(__('Are you sure? This field\'s value will be replaced by the value in the original entry.'))) return;
      this.localizedFields = this.localizedFields.filter(function (field) {
        return field !== handle;
      });
      this.$refs.container.setFieldValue(handle, this.originValues[handle]); // Update the meta for this field. For instance, a relationship field would have its data preloaded into it.
      // If you sync the field, the preloaded data would be outdated and an ID would show instead of the titles.

      this.meta[handle] = this.originMeta[handle];
    },
    desyncField: function desyncField(handle) {
      if (!this.localizedFields.includes(handle)) this.localizedFields.push(handle);
      this.$refs.container.dirty();
    }
  },
  mounted: function mounted() {
    var _this3 = this;

    this.$keys.bindGlobal(['mod+s'], function (e) {
      e.preventDefault();

      _this3.save();
    });
  },
  created: function created() {
    window.history.replaceState({}, document.title, document.location.href.replace('created=true', ''));
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'seo-meta-title-fieldtype',
  mixins: [Fieldtype],
  inject: ['storeName'],
  computed: {
    placeholder: function placeholder() {
      return this.siteDefaults.seo_title || this.pageTitle || '';
    },
    siteName: function siteName() {
      var _this$siteDefaults$ti, _this$siteDefaults$si;

      var titleSeparator = (_this$siteDefaults$ti = this.siteDefaults.title_separator) !== null && _this$siteDefaults$ti !== void 0 ? _this$siteDefaults$ti : '';
      var siteName = (_this$siteDefaults$si = this.siteDefaults.site_name) !== null && _this$siteDefaults$si !== void 0 ? _this$siteDefaults$si : '';
      return "".concat(titleSeparator, " ").concat(siteName);
    },
    siteDefaults: function siteDefaults() {
      return this.meta[this.baseStore.site] || this.meta[this.statamicStore.selectedSite];
    },
    baseStore: function baseStore() {
      return this.$store.state.publish[this.storeName];
    },
    statamicStore: function statamicStore() {
      return this.$store.state.statamic.config;
    },
    pageTitle: function pageTitle() {
      return this.baseStore.values.title;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  props: {
    sites: {
      type: Array,
      required: true
    },
    value: {
      type: String,
      required: true
    }
  },
  computed: {
    site: function site() {
      return _.findWhere(this.sites, {
        handle: this.value
      });
    }
  }
});

/***/ }),

/***/ "./resources/js/components/DefaultsPublishForm.vue":
/*!*********************************************************!*\
  !*** ./resources/js/components/DefaultsPublishForm.vue ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _DefaultsPublishForm_vue_vue_type_template_id_026f8226___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./DefaultsPublishForm.vue?vue&type=template&id=026f8226& */ "./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226&");
/* harmony import */ var _DefaultsPublishForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./DefaultsPublishForm.vue?vue&type=script&lang=js& */ "./resources/js/components/DefaultsPublishForm.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__.default)(
  _DefaultsPublishForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__.default,
  _DefaultsPublishForm_vue_vue_type_template_id_026f8226___WEBPACK_IMPORTED_MODULE_0__.render,
  _DefaultsPublishForm_vue_vue_type_template_id_026f8226___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/js/components/DefaultsPublishForm.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./resources/js/components/SeoMetaTitleFieldtype.vue":
/*!***********************************************************!*\
  !*** ./resources/js/components/SeoMetaTitleFieldtype.vue ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SeoMetaTitleFieldtype_vue_vue_type_template_id_ac0dd7fa___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SeoMetaTitleFieldtype.vue?vue&type=template&id=ac0dd7fa& */ "./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=template&id=ac0dd7fa&");
/* harmony import */ var _SeoMetaTitleFieldtype_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SeoMetaTitleFieldtype.vue?vue&type=script&lang=js& */ "./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__.default)(
  _SeoMetaTitleFieldtype_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__.default,
  _SeoMetaTitleFieldtype_vue_vue_type_template_id_ac0dd7fa___WEBPACK_IMPORTED_MODULE_0__.render,
  _SeoMetaTitleFieldtype_vue_vue_type_template_id_ac0dd7fa___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/js/components/SeoMetaTitleFieldtype.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./vendor/statamic/cms/resources/js/components/SiteSelector.vue":
/*!**********************************************************************!*\
  !*** ./vendor/statamic/cms/resources/js/components/SiteSelector.vue ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SiteSelector_vue_vue_type_template_id_3d567ff6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SiteSelector.vue?vue&type=template&id=3d567ff6& */ "./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=template&id=3d567ff6&");
/* harmony import */ var _SiteSelector_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SiteSelector.vue?vue&type=script&lang=js& */ "./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__.default)(
  _SiteSelector_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__.default,
  _SiteSelector_vue_vue_type_template_id_3d567ff6___WEBPACK_IMPORTED_MODULE_0__.render,
  _SiteSelector_vue_vue_type_template_id_3d567ff6___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "vendor/statamic/cms/resources/js/components/SiteSelector.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./resources/js/components/DefaultsPublishForm.vue?vue&type=script&lang=js&":
/*!**********************************************************************************!*\
  !*** ./resources/js/components/DefaultsPublishForm.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./DefaultsPublishForm.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__.default); 

/***/ }),

/***/ "./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=script&lang=js&":
/*!************************************************************************************!*\
  !*** ./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=script&lang=js& ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_SeoMetaTitleFieldtype_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SeoMetaTitleFieldtype.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_SeoMetaTitleFieldtype_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__.default); 

/***/ }),

/***/ "./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************!*\
  !*** ./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_SiteSelector_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SiteSelector.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_SiteSelector_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__.default); 

/***/ }),

/***/ "./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226&":
/*!****************************************************************************************!*\
  !*** ./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226& ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_template_id_026f8226___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_template_id_026f8226___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_template_id_026f8226___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./DefaultsPublishForm.vue?vue&type=template&id=026f8226& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226&");


/***/ }),

/***/ "./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=template&id=ac0dd7fa&":
/*!******************************************************************************************!*\
  !*** ./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=template&id=ac0dd7fa& ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SeoMetaTitleFieldtype_vue_vue_type_template_id_ac0dd7fa___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SeoMetaTitleFieldtype_vue_vue_type_template_id_ac0dd7fa___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SeoMetaTitleFieldtype_vue_vue_type_template_id_ac0dd7fa___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SeoMetaTitleFieldtype.vue?vue&type=template&id=ac0dd7fa& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=template&id=ac0dd7fa&");


/***/ }),

/***/ "./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=template&id=3d567ff6&":
/*!*****************************************************************************************************!*\
  !*** ./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=template&id=3d567ff6& ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SiteSelector_vue_vue_type_template_id_3d567ff6___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SiteSelector_vue_vue_type_template_id_3d567ff6___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SiteSelector_vue_vue_type_template_id_3d567ff6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SiteSelector.vue?vue&type=template&id=3d567ff6& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=template&id=3d567ff6&");


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226&":
/*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226& ***!
  \*******************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    [
      _c(
        "header",
        { staticClass: "mb-3" },
        [
          _c("breadcrumb", {
            attrs: { url: _vm.defaultsUrl, title: _vm.defaultsTitle }
          }),
          _vm._v(" "),
          _c(
            "div",
            { staticClass: "flex items-center" },
            [
              _c("h1", {
                staticClass: "flex-1",
                domProps: { textContent: _vm._s(_vm.title) }
              }),
              _vm._v(" "),
              !_vm.canEdit
                ? _c(
                    "div",
                    { staticClass: "pt-px text-2xs text-grey-60 flex" },
                    [
                      _c("svg-icon", {
                        staticClass: "w-4 mr-sm -mt-sm",
                        attrs: { name: "lock" }
                      }),
                      _vm._v(
                        " " + _vm._s(_vm.__("Read Only")) + "\n            "
                      )
                    ],
                    1
                  )
                : _vm._e(),
              _vm._v(" "),
              _vm.localizations.length > 1
                ? _c("site-selector", {
                    staticClass: "ml-2",
                    attrs: { sites: _vm.localizations, value: _vm.site },
                    on: { input: _vm.localizationSelected }
                  })
                : _vm._e(),
              _vm._v(" "),
              _vm.canEdit
                ? _c("button", {
                    staticClass: "btn-primary min-w-100 ml-2",
                    class: { "opacity-25": !_vm.canSave },
                    attrs: { disabled: !_vm.canSave },
                    domProps: { textContent: _vm._s(_vm.__("Save")) },
                    on: {
                      click: function($event) {
                        $event.preventDefault()
                        return _vm.save.apply(null, arguments)
                      }
                    }
                  })
                : _vm._e()
            ],
            1
          )
        ],
        1
      ),
      _vm._v(" "),
      _c("publish-container", {
        ref: "container",
        attrs: {
          name: _vm.publishContainer,
          blueprint: _vm.fieldset,
          values: _vm.values,
          reference: _vm.initialReference,
          meta: _vm.meta,
          errors: _vm.errors,
          site: _vm.site,
          "localized-fields": _vm.localizedFields,
          "is-root": _vm.isRoot
        },
        on: {
          updated: function($event) {
            _vm.values = $event
          }
        },
        scopedSlots: _vm._u([
          {
            key: "default",
            fn: function(ref) {
              var container = ref.container
              var components = ref.components
              var setFieldMeta = ref.setFieldMeta
              return _c(
                "div",
                {},
                [
                  _vm._l(components, function(component) {
                    return _c(
                      component.name,
                      _vm._b(
                        {
                          key: component.name,
                          tag: "component",
                          attrs: { container: container }
                        },
                        "component",
                        component.props,
                        false
                      )
                    )
                  }),
                  _vm._v(" "),
                  _c("publish-sections", {
                    attrs: {
                      "read-only": !_vm.canEdit,
                      syncable: _vm.hasOrigin,
                      "can-toggle-labels": true,
                      "enable-sidebar": false
                    },
                    on: {
                      updated: _vm.setFieldValue,
                      "meta-updated": setFieldMeta,
                      synced: _vm.syncField,
                      desynced: _vm.desyncField,
                      focus: function($event) {
                        return container.$emit("focus", $event)
                      },
                      blur: function($event) {
                        return container.$emit("blur", $event)
                      }
                    }
                  })
                ],
                2
              )
            }
          }
        ])
      })
    ],
    1
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=template&id=ac0dd7fa&":
/*!*********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SeoMetaTitleFieldtype.vue?vue&type=template&id=ac0dd7fa& ***!
  \*********************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    [
      _c("text-input", {
        attrs: {
          placeholder: _vm.placeholder,
          value: _vm.value,
          append: _vm.siteName,
          limit: 70
        },
        on: { input: _vm.update }
      })
    ],
    1
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=template&id=3d567ff6&":
/*!********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./vendor/statamic/cms/resources/js/components/SiteSelector.vue?vue&type=template&id=3d567ff6& ***!
  \********************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("v-select", {
    staticClass: "text-sm",
    attrs: {
      value: _vm.site,
      clearable: false,
      searchable: false,
      "get-option-label": function(site) {
        return site.name
      },
      options: _vm.sites
    },
    on: {
      input: function($event) {
        return _vm.$emit("input", $event)
      }
    }
  })
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js":
/*!********************************************************************!*\
  !*** ./node_modules/vue-loader/lib/runtime/componentNormalizer.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ normalizeComponent)
/* harmony export */ });
/* globals __VUE_SSR_CONTEXT__ */

// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).
// This module is a runtime utility for cleaner component module output and will
// be included in the final webpack user bundle.

function normalizeComponent (
  scriptExports,
  render,
  staticRenderFns,
  functionalTemplate,
  injectStyles,
  scopeId,
  moduleIdentifier, /* server only */
  shadowMode /* vue-cli only */
) {
  // Vue.extend constructor export interop
  var options = typeof scriptExports === 'function'
    ? scriptExports.options
    : scriptExports

  // render functions
  if (render) {
    options.render = render
    options.staticRenderFns = staticRenderFns
    options._compiled = true
  }

  // functional template
  if (functionalTemplate) {
    options.functional = true
  }

  // scopedId
  if (scopeId) {
    options._scopeId = 'data-v-' + scopeId
  }

  var hook
  if (moduleIdentifier) { // server build
    hook = function (context) {
      // 2.3 injection
      context =
        context || // cached call
        (this.$vnode && this.$vnode.ssrContext) || // stateful
        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional
      // 2.2 with runInNewContext: true
      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {
        context = __VUE_SSR_CONTEXT__
      }
      // inject component styles
      if (injectStyles) {
        injectStyles.call(this, context)
      }
      // register component module identifier for async chunk inferrence
      if (context && context._registeredComponents) {
        context._registeredComponents.add(moduleIdentifier)
      }
    }
    // used by ssr in case component is cached and beforeCreate
    // never gets called
    options._ssrRegister = hook
  } else if (injectStyles) {
    hook = shadowMode
      ? function () {
        injectStyles.call(
          this,
          (options.functional ? this.parent : this).$root.$options.shadowRoot
        )
      }
      : injectStyles
  }

  if (hook) {
    if (options.functional) {
      // for template-only hot-reload because in that case the render fn doesn't
      // go through the normalizer
      options._injectStyles = hook
      // register for functional component in vue file
      var originalRender = options.render
      options.render = function renderWithStyleInjection (h, context) {
        hook.call(context)
        return originalRender(h, context)
      }
    } else {
      // inject component registration as beforeCreate hook
      var existing = options.beforeCreate
      options.beforeCreate = existing
        ? [].concat(existing, hook)
        : [hook]
    }
  }

  return {
    exports: scriptExports,
    options: options
  }
}


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!****************************!*\
  !*** ./resources/js/cp.js ***!
  \****************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _components_DefaultsPublishForm__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/DefaultsPublishForm */ "./resources/js/components/DefaultsPublishForm.vue");
/* harmony import */ var _components_SeoMetaTitleFieldtype__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/SeoMetaTitleFieldtype */ "./resources/js/components/SeoMetaTitleFieldtype.vue");


Statamic.booting(function () {
  Statamic.component('defaults-publish-form', _components_DefaultsPublishForm__WEBPACK_IMPORTED_MODULE_0__.default);
  Statamic.component('seo_meta_title-fieldtype', _components_SeoMetaTitleFieldtype__WEBPACK_IMPORTED_MODULE_1__.default);
});
})();

/******/ })()
;