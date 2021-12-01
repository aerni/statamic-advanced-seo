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
  props: {
    publishContainer: String,
    initialReference: String,
    initialFieldset: Object,
    initialValues: Object,
    initialMeta: Object,
    initialTitle: String,
    initialLocalizations: Array,
    initialLocalizedFields: Array,
    initialHasOrigin: Boolean,
    initialOriginValues: Object,
    initialOriginMeta: Object,
    initialSite: String,
    breadcrumbs: Array,
    initialActions: Object,
    method: String,
    isCreating: Boolean,
    initialReadOnly: Boolean,
    initialIsRoot: Boolean,
    contentType: String
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
      isRoot: this.initialIsRoot,
      readOnly: this.initialReadOnly
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
      return !this.readOnly && this.isDirty && !this.somethingIsLoading;
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
    },
    computedBreadcrumbs: function computedBreadcrumbs() {
      var breadcrumbs = {
        'url': this.breadcrumbs[0].url,
        'text': this.breadcrumbs[0].text
      };

      if (this.contentType !== 'site') {
        breadcrumbs.text = "".concat(this.breadcrumbs[0].text, " (").concat(this.breadcrumbs[1].text, ")");
      }

      return breadcrumbs;
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

      this.$dirty.remove(this.publishContainer);
      this.localizing = localization.handle;

      if (this.isBase) {
        window.history.replaceState({}, '', localization.url);
      }

      this.$axios.get(localization.url).then(function (response) {
        var data = response.data;
        _this2.values = data.values;
        _this2.originValues = data.originValues;
        _this2.originMeta = data.originMeta;
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

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************************************************************************/
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
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'social-images-preview-fieldtype',
  mixins: [Fieldtype]
});

/***/ }),

/***/ "./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_laravel_mix_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/laravel-mix/node_modules/css-loader/dist/runtime/api.js */ "./node_modules/laravel-mix/node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_laravel_mix_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_laravel_mix_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_laravel_mix_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, "\n.remove-border-bottom[data-v-026f8226] .publish-sidebar .publish-section-actions {\n    border-bottom-width: 0;\n}\n", ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/laravel-mix/node_modules/css-loader/dist/runtime/api.js":
/*!******************************************************************************!*\
  !*** ./node_modules/laravel-mix/node_modules/css-loader/dist/runtime/api.js ***!
  \******************************************************************************/
/***/ ((module) => {



/*
  MIT License http://www.opensource.org/licenses/mit-license.php
  Author Tobias Koppers @sokra
*/
// css base code, injected by the css-loader
// eslint-disable-next-line func-names
module.exports = function (cssWithMappingToString) {
  var list = []; // return the list of modules as css string

  list.toString = function toString() {
    return this.map(function (item) {
      var content = cssWithMappingToString(item);

      if (item[2]) {
        return "@media ".concat(item[2], " {").concat(content, "}");
      }

      return content;
    }).join("");
  }; // import a list of modules into the list
  // eslint-disable-next-line func-names


  list.i = function (modules, mediaQuery, dedupe) {
    if (typeof modules === "string") {
      // eslint-disable-next-line no-param-reassign
      modules = [[null, modules, ""]];
    }

    var alreadyImportedModules = {};

    if (dedupe) {
      for (var i = 0; i < this.length; i++) {
        // eslint-disable-next-line prefer-destructuring
        var id = this[i][0];

        if (id != null) {
          alreadyImportedModules[id] = true;
        }
      }
    }

    for (var _i = 0; _i < modules.length; _i++) {
      var item = [].concat(modules[_i]);

      if (dedupe && alreadyImportedModules[item[0]]) {
        // eslint-disable-next-line no-continue
        continue;
      }

      if (mediaQuery) {
        if (!item[2]) {
          item[2] = mediaQuery;
        } else {
          item[2] = "".concat(mediaQuery, " and ").concat(item[2]);
        }
      }

      list.push(item);
    }
  };

  return list;
};

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_8_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_8_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_style_index_0_id_026f8226_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[1]!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[2]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css& */ "./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css&");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_8_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_8_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_style_index_0_id_026f8226_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__["default"], options);



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_8_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_8_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_style_index_0_id_026f8226_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__["default"].locals || {});

/***/ }),

/***/ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js":
/*!****************************************************************************!*\
  !*** ./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js ***!
  \****************************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var isOldIE = function isOldIE() {
  var memo;
  return function memorize() {
    if (typeof memo === 'undefined') {
      // Test for IE <= 9 as proposed by Browserhacks
      // @see http://browserhacks.com/#hack-e71d8692f65334173fee715c222cb805
      // Tests for existence of standard globals is to allow style-loader
      // to operate correctly into non-standard environments
      // @see https://github.com/webpack-contrib/style-loader/issues/177
      memo = Boolean(window && document && document.all && !window.atob);
    }

    return memo;
  };
}();

var getTarget = function getTarget() {
  var memo = {};
  return function memorize(target) {
    if (typeof memo[target] === 'undefined') {
      var styleTarget = document.querySelector(target); // Special case to return head of iframe instead of iframe itself

      if (window.HTMLIFrameElement && styleTarget instanceof window.HTMLIFrameElement) {
        try {
          // This will throw an exception if access to iframe is blocked
          // due to cross-origin restrictions
          styleTarget = styleTarget.contentDocument.head;
        } catch (e) {
          // istanbul ignore next
          styleTarget = null;
        }
      }

      memo[target] = styleTarget;
    }

    return memo[target];
  };
}();

var stylesInDom = [];

function getIndexByIdentifier(identifier) {
  var result = -1;

  for (var i = 0; i < stylesInDom.length; i++) {
    if (stylesInDom[i].identifier === identifier) {
      result = i;
      break;
    }
  }

  return result;
}

function modulesToDom(list, options) {
  var idCountMap = {};
  var identifiers = [];

  for (var i = 0; i < list.length; i++) {
    var item = list[i];
    var id = options.base ? item[0] + options.base : item[0];
    var count = idCountMap[id] || 0;
    var identifier = "".concat(id, " ").concat(count);
    idCountMap[id] = count + 1;
    var index = getIndexByIdentifier(identifier);
    var obj = {
      css: item[1],
      media: item[2],
      sourceMap: item[3]
    };

    if (index !== -1) {
      stylesInDom[index].references++;
      stylesInDom[index].updater(obj);
    } else {
      stylesInDom.push({
        identifier: identifier,
        updater: addStyle(obj, options),
        references: 1
      });
    }

    identifiers.push(identifier);
  }

  return identifiers;
}

function insertStyleElement(options) {
  var style = document.createElement('style');
  var attributes = options.attributes || {};

  if (typeof attributes.nonce === 'undefined') {
    var nonce =  true ? __webpack_require__.nc : 0;

    if (nonce) {
      attributes.nonce = nonce;
    }
  }

  Object.keys(attributes).forEach(function (key) {
    style.setAttribute(key, attributes[key]);
  });

  if (typeof options.insert === 'function') {
    options.insert(style);
  } else {
    var target = getTarget(options.insert || 'head');

    if (!target) {
      throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");
    }

    target.appendChild(style);
  }

  return style;
}

function removeStyleElement(style) {
  // istanbul ignore if
  if (style.parentNode === null) {
    return false;
  }

  style.parentNode.removeChild(style);
}
/* istanbul ignore next  */


var replaceText = function replaceText() {
  var textStore = [];
  return function replace(index, replacement) {
    textStore[index] = replacement;
    return textStore.filter(Boolean).join('\n');
  };
}();

function applyToSingletonTag(style, index, remove, obj) {
  var css = remove ? '' : obj.media ? "@media ".concat(obj.media, " {").concat(obj.css, "}") : obj.css; // For old IE

  /* istanbul ignore if  */

  if (style.styleSheet) {
    style.styleSheet.cssText = replaceText(index, css);
  } else {
    var cssNode = document.createTextNode(css);
    var childNodes = style.childNodes;

    if (childNodes[index]) {
      style.removeChild(childNodes[index]);
    }

    if (childNodes.length) {
      style.insertBefore(cssNode, childNodes[index]);
    } else {
      style.appendChild(cssNode);
    }
  }
}

function applyToTag(style, options, obj) {
  var css = obj.css;
  var media = obj.media;
  var sourceMap = obj.sourceMap;

  if (media) {
    style.setAttribute('media', media);
  } else {
    style.removeAttribute('media');
  }

  if (sourceMap && typeof btoa !== 'undefined') {
    css += "\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap)))), " */");
  } // For old IE

  /* istanbul ignore if  */


  if (style.styleSheet) {
    style.styleSheet.cssText = css;
  } else {
    while (style.firstChild) {
      style.removeChild(style.firstChild);
    }

    style.appendChild(document.createTextNode(css));
  }
}

var singleton = null;
var singletonCounter = 0;

function addStyle(obj, options) {
  var style;
  var update;
  var remove;

  if (options.singleton) {
    var styleIndex = singletonCounter++;
    style = singleton || (singleton = insertStyleElement(options));
    update = applyToSingletonTag.bind(null, style, styleIndex, false);
    remove = applyToSingletonTag.bind(null, style, styleIndex, true);
  } else {
    style = insertStyleElement(options);
    update = applyToTag.bind(null, style, options);

    remove = function remove() {
      removeStyleElement(style);
    };
  }

  update(obj);
  return function updateStyle(newObj) {
    if (newObj) {
      if (newObj.css === obj.css && newObj.media === obj.media && newObj.sourceMap === obj.sourceMap) {
        return;
      }

      update(obj = newObj);
    } else {
      remove();
    }
  };
}

module.exports = function (list, options) {
  options = options || {}; // Force single-tag solution on IE6-9, which has a hard limit on the # of <style>
  // tags it will allow on a page

  if (!options.singleton && typeof options.singleton !== 'boolean') {
    options.singleton = isOldIE();
  }

  list = list || [];
  var lastIdentifiers = modulesToDom(list, options);
  return function update(newList) {
    newList = newList || [];

    if (Object.prototype.toString.call(newList) !== '[object Array]') {
      return;
    }

    for (var i = 0; i < lastIdentifiers.length; i++) {
      var identifier = lastIdentifiers[i];
      var index = getIndexByIdentifier(identifier);
      stylesInDom[index].references--;
    }

    var newLastIdentifiers = modulesToDom(newList, options);

    for (var _i = 0; _i < lastIdentifiers.length; _i++) {
      var _identifier = lastIdentifiers[_i];

      var _index = getIndexByIdentifier(_identifier);

      if (stylesInDom[_index].references === 0) {
        stylesInDom[_index].updater();

        stylesInDom.splice(_index, 1);
      }
    }

    lastIdentifiers = newLastIdentifiers;
  };
};

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
/* harmony import */ var _DefaultsPublishForm_vue_vue_type_template_id_026f8226_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./DefaultsPublishForm.vue?vue&type=template&id=026f8226&scoped=true& */ "./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226&scoped=true&");
/* harmony import */ var _DefaultsPublishForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./DefaultsPublishForm.vue?vue&type=script&lang=js& */ "./resources/js/components/DefaultsPublishForm.vue?vue&type=script&lang=js&");
/* harmony import */ var _DefaultsPublishForm_vue_vue_type_style_index_0_id_026f8226_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css& */ "./resources/js/components/DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _DefaultsPublishForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _DefaultsPublishForm_vue_vue_type_template_id_026f8226_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _DefaultsPublishForm_vue_vue_type_template_id_026f8226_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "026f8226",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/js/components/DefaultsPublishForm.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./resources/js/components/SocialImagesPreviewFieldtype.vue":
/*!******************************************************************!*\
  !*** ./resources/js/components/SocialImagesPreviewFieldtype.vue ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SocialImagesPreviewFieldtype_vue_vue_type_template_id_73152fc8___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SocialImagesPreviewFieldtype.vue?vue&type=template&id=73152fc8& */ "./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=template&id=73152fc8&");
/* harmony import */ var _SocialImagesPreviewFieldtype_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SocialImagesPreviewFieldtype.vue?vue&type=script&lang=js& */ "./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SocialImagesPreviewFieldtype_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SocialImagesPreviewFieldtype_vue_vue_type_template_id_73152fc8___WEBPACK_IMPORTED_MODULE_0__.render,
  _SocialImagesPreviewFieldtype_vue_vue_type_template_id_73152fc8___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/js/components/SocialImagesPreviewFieldtype.vue"
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
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************!*\
  !*** ./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_SocialImagesPreviewFieldtype_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SocialImagesPreviewFieldtype.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_SocialImagesPreviewFieldtype_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./resources/js/components/DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css&":
/*!******************************************************************************************************************!*\
  !*** ./resources/js/components/DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css& ***!
  \******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_8_0_rules_0_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_8_0_rules_0_use_2_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_style_index_0_id_026f8226_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[1]!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[2]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-8[0].rules[0].use[2]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=style&index=0&id=026f8226&scoped=true&lang=css&");


/***/ }),

/***/ "./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226&scoped=true&":
/*!****************************************************************************************************!*\
  !*** ./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226&scoped=true& ***!
  \****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_template_id_026f8226_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_template_id_026f8226_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_DefaultsPublishForm_vue_vue_type_template_id_026f8226_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./DefaultsPublishForm.vue?vue&type=template&id=026f8226&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226&scoped=true&");


/***/ }),

/***/ "./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=template&id=73152fc8&":
/*!*************************************************************************************************!*\
  !*** ./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=template&id=73152fc8& ***!
  \*************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SocialImagesPreviewFieldtype_vue_vue_type_template_id_73152fc8___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SocialImagesPreviewFieldtype_vue_vue_type_template_id_73152fc8___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SocialImagesPreviewFieldtype_vue_vue_type_template_id_73152fc8___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SocialImagesPreviewFieldtype.vue?vue&type=template&id=73152fc8& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=template&id=73152fc8&");


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/DefaultsPublishForm.vue?vue&type=template&id=026f8226&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    { staticClass: "remove-border-bottom" },
    [
      _c(
        "header",
        { staticClass: "mb-3" },
        [
          _c("breadcrumb", {
            attrs: {
              url: _vm.computedBreadcrumbs.url,
              title: _vm.computedBreadcrumbs.text,
            },
          }),
          _vm._v(" "),
          _c("div", { staticClass: "flex items-center" }, [
            _c("h1", {
              staticClass: "flex-1",
              domProps: { textContent: _vm._s(_vm.title) },
            }),
            _vm._v(" "),
            _vm.readOnly
              ? _c(
                  "div",
                  { staticClass: "pt-px text-2xs text-grey-60 flex" },
                  [
                    _c("svg-icon", {
                      staticClass: "w-4 mr-sm -mt-sm",
                      attrs: { name: "lock" },
                    }),
                    _vm._v(
                      " " + _vm._s(_vm.__("Read Only")) + "\n            "
                    ),
                  ],
                  1
                )
              : _vm._e(),
            _vm._v(" "),
            !_vm.readOnly
              ? _c("button", {
                  staticClass: "btn-primary min-w-100 ml-2",
                  class: { "opacity-25": !_vm.canSave },
                  attrs: { disabled: !_vm.canSave },
                  domProps: { textContent: _vm._s(_vm.__("Save")) },
                  on: {
                    click: function ($event) {
                      $event.preventDefault()
                      return _vm.save.apply(null, arguments)
                    },
                  },
                })
              : _vm._e(),
          ]),
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
          "is-root": _vm.isRoot,
        },
        on: {
          updated: function ($event) {
            _vm.values = $event
          },
        },
        scopedSlots: _vm._u([
          {
            key: "default",
            fn: function (ref) {
              var container = ref.container
              var components = ref.components
              var setFieldMeta = ref.setFieldMeta
              return _c(
                "div",
                {},
                [
                  _vm._l(components, function (component) {
                    return _c(
                      component.name,
                      _vm._b(
                        {
                          key: component.name,
                          tag: "component",
                          attrs: { container: container },
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
                      "read-only": _vm.readOnly,
                      syncable: _vm.hasOrigin,
                      "can-toggle-labels": true,
                    },
                    on: {
                      updated: _vm.setFieldValue,
                      "meta-updated": setFieldMeta,
                      synced: _vm.syncField,
                      desynced: _vm.desyncField,
                      focus: function ($event) {
                        return container.$emit("focus", $event)
                      },
                      blur: function ($event) {
                        return container.$emit("blur", $event)
                      },
                    },
                    scopedSlots: _vm._u(
                      [
                        {
                          key: "actions",
                          fn: function (ref) {
                            var shouldShowSidebar = ref.shouldShowSidebar
                            return [
                              _c(
                                "div",
                                { staticClass: "p-2" },
                                [
                                  _c("label", {
                                    staticClass:
                                      "publish-field-label font-medium mb-1",
                                    domProps: {
                                      textContent: _vm._s(_vm.__("Sites")),
                                    },
                                  }),
                                  _vm._v(" "),
                                  _vm._l(_vm.localizations, function (option) {
                                    return _c(
                                      "div",
                                      {
                                        key: option.handle,
                                        staticClass:
                                          "text-sm flex items-center -mx-2 px-2 py-1 cursor-pointer",
                                        class: option.active
                                          ? "bg-blue-100"
                                          : "hover:bg-grey-20",
                                        on: {
                                          click: function ($event) {
                                            return _vm.localizationSelected(
                                              option
                                            )
                                          },
                                        },
                                      },
                                      [
                                        _c(
                                          "div",
                                          {
                                            staticClass:
                                              "flex-1 flex items-center",
                                          },
                                          [
                                            _vm._v(
                                              "\n                                " +
                                                _vm._s(option.name) +
                                                "\n                                "
                                            ),
                                            _vm.localizing === option.handle
                                              ? _c("loading-graphic", {
                                                  staticClass:
                                                    "ml-1 flex items-center",
                                                  staticStyle: {
                                                    "padding-bottom": "0.05em",
                                                  },
                                                  attrs: { size: 14, text: "" },
                                                })
                                              : _vm._e(),
                                          ],
                                          1
                                        ),
                                        _vm._v(" "),
                                        option.origin
                                          ? _c("div", {
                                              staticClass: "badge-sm bg-orange",
                                              domProps: {
                                                textContent: _vm._s(
                                                  _vm.__("Origin")
                                                ),
                                              },
                                            })
                                          : _vm._e(),
                                        _vm._v(" "),
                                        option.active
                                          ? _c("div", {
                                              staticClass: "badge-sm bg-blue",
                                              domProps: {
                                                textContent: _vm._s(
                                                  _vm.__("Active")
                                                ),
                                              },
                                            })
                                          : _vm._e(),
                                        _vm._v(" "),
                                        option.root &&
                                        !option.origin &&
                                        !option.active
                                          ? _c("div", {
                                              staticClass: "badge-sm bg-purple",
                                              domProps: {
                                                textContent: _vm._s(
                                                  _vm.__("Root")
                                                ),
                                              },
                                            })
                                          : _vm._e(),
                                      ]
                                    )
                                  }),
                                ],
                                2
                              ),
                            ]
                          },
                        },
                      ],
                      null,
                      true
                    ),
                  }),
                ],
                2
              )
            },
          },
        ]),
      }),
    ],
    1
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=template&id=73152fc8&":
/*!****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./resources/js/components/SocialImagesPreviewFieldtype.vue?vue&type=template&id=73152fc8& ***!
  \****************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", [_c("img", { attrs: { src: this.meta.image } })])
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
/******/ 			id: moduleId,
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
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
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
/* harmony import */ var _components_SocialImagesPreviewFieldtype__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/SocialImagesPreviewFieldtype */ "./resources/js/components/SocialImagesPreviewFieldtype.vue");


Statamic.booting(function () {
  Statamic.component('defaults-publish-form', _components_DefaultsPublishForm__WEBPACK_IMPORTED_MODULE_0__["default"]);
  Statamic.component('social_images_preview-fieldtype', _components_SocialImagesPreviewFieldtype__WEBPACK_IMPORTED_MODULE_1__["default"]);
});
})();

/******/ })()
;