const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

function createJqueryStub() {
  const store = new Map();

  function makeElement(key) {
    if (!store.has(key)) {
      store.set(key, {
        value: '',
        textValue: '',
        htmlValue: '',
        checked: false,
        classes: new Set(),
        attrs: {},
      });
    }
    const state = store.get(key);
    return {
      val(value) {
        if (arguments.length === 0) return state.value;
        state.value = String(value);
        return this;
      },
      text(value) {
        if (arguments.length === 0) return state.textValue;
        state.textValue = String(value);
        return this;
      },
      html(value) {
        if (arguments.length === 0) return state.htmlValue;
        state.htmlValue = String(value);
        return this;
      },
      prop(name, value) {
        if (arguments.length === 1) return state[name];
        state[name] = value;
        return this;
      },
      attr(name, value) {
        if (arguments.length === 1) return state.attrs[name];
        state.attrs[name] = String(value);
        return this;
      },
      is(selector) {
        return selector === ':checked' ? !!state.checked : false;
      },
      addClass(name) {
        state.classes.add(name);
        return this;
      },
      removeClass(name) {
        state.classes.delete(name);
        return this;
      },
      show() { return this; },
      hide() { return this; },
      on() { return this; },
      data() { return undefined; },
      each() { return this; },
      find() { return makeElement(key + ' find'); },
      eq() { return makeElement(key + ' eq'); },
    };
  }

  function $(selector) {
    if (selector && selector.__jqueryKey) return makeElement(selector.__jqueryKey);
    if (typeof selector === 'string' && selector.startsWith('<')) {
      return makeElement('__html__' + selector);
    }
    return makeElement(String(selector));
  }

  $.trim = (value) => String(value || '').trim();
  $.extend = (target, ...sources) => Object.assign(target, ...sources);
  $.inArray = (value, list) => list.indexOf(value);
  $.each = (collection, callback) => {
    if (Array.isArray(collection)) {
      collection.forEach((value, index) => callback(index, value));
      return;
    }
    Object.keys(collection || {}).forEach((key) => callback(key, collection[key]));
  };
  $.__store = store;
  return $;
}

function loadRouteScript() {
  const source = fs.readFileSync('index.php', 'utf8');
  const scripts = [...source.matchAll(/<script>([\s\S]*?)<\/script>/g)];
  const match = scripts.at(-1);
  assert(match, 'index.php custom script block should be extractable');

  const $ = createJqueryStub();
  const context = {
    console,
    URLSearchParams,
    encodeURIComponent,
    Date,
    Math,
    parseFloat,
    parseInt,
    isNaN,
    setTimeout() { return 0; },
    clearTimeout() {},
    window: {
      location: { pathname: '/demo/php/map/routing_path/index.php', search: '' },
      ROUTING_CONFIG: { addressApiUrl: '' },
      history: { replaceState() {} },
      speechSynthesis: { cancel() {}, speak() {} },
      SpeechSynthesisUtterance: function SpeechSynthesisUtterance(text) { this.text = text; },
    },
    document: {
      getElementById() {
        return {
          addEventListener() {},
        };
      },
    },
    $,
    jQuery: $,
    Easymap: function Easymap() {
      return {
        switchMap() {},
        resize() {},
        addMenu() {},
        revXY() { return new context.dgXY(120, 24); },
        addItem(item) { return item; },
        removeItem() {},
        panToXYZ() {},
        getDGSExtent() { return null; },
        zoomToExtent() {},
      };
    },
    dgXY: function dgXY(x, y) { this.x = x; this.y = y; },
    dgXYZ: function dgXYZ(x, y, z) { this.x = x; this.y = y; this.z = z; },
    dgMarker: function dgMarker(xy) { this.xy = xy; },
    dgWKT: function dgWKT(wkts) { this.wkts = wkts; },
    dgMenuFunc: function dgMenuFunc(label, callback) { this.label = label; this.callback = callback; },
    dialogMyBoxOn() {},
    smallComment() {},
    drawArrowTojQDom() { return { destroy() {} }; },
    drawCircleTojQDom() { return { remove() {} }; },
    myAjax_async_json() {},
  };
  context.window.window = context.window;
  context.window.SpeechSynthesisUtterance = context.window.SpeechSynthesisUtterance;
  context.global = context;
  vm.createContext(context);
  vm.runInContext(match[1], context, { filename: 'index.php<script>' });
  return context;
}

function testApplyResolvedPointStoresMetadata() {
  const ctx = loadRouteScript();
  assert.strictEqual(typeof ctx.applyResolvedPoint, 'function');

  ctx.applyResolvedPoint('start', {
    title: '逢甲大學',
    subtitle: '台中市西屯區文華路100',
    lon: 120.64833886432,
    lat: 24.18005755,
    source: 'osm_poi',
    quality_flag: 'osm_reference',
  }, '逢甲');

  assert.strictEqual(ctx.STATE.startXY.x, 120.64833886432);
  assert.strictEqual(ctx.STATE.startXY.y, 24.18005755);
  assert.strictEqual(ctx.STATE.startMeta.label, '逢甲大學');
  assert.strictEqual(ctx.STATE.startMeta.subtitle, '台中市西屯區文華路100');
  assert.strictEqual(ctx.STATE.startMeta.source, 'osm_poi');
  assert.strictEqual(ctx.STATE.startMeta.quality_flag, 'osm_reference');
  assert.strictEqual(ctx.STATE.startMeta.resolved_by, 'autocomplete');
  assert.strictEqual(ctx.$('#inp-start').val(), '逢甲大學');
}

function testShareUrlIncludesHumanLabels() {
  const ctx = loadRouteScript();
  ctx.STATE.startXY = new ctx.dgXY(120.64833886432, 24.18005755);
  ctx.STATE.endXY = new ctx.dgXY(120.6656964, 24.1193913);
  ctx.STATE.passXY = [new ctx.dgXY(120.6501, 24.1502)];
  ctx.STATE.startMeta = { label: '逢甲大學' };
  ctx.STATE.endMeta = { label: '台中市南區新和街1號' };
  ctx.STATE.passMeta = [{ label: '中途點' }];

  const url = ctx.buildRouteShareUrl();
  const params = new URLSearchParams(url.split('?')[1]);

  assert.strictEqual(params.get('start_label'), '逢甲大學');
  assert.strictEqual(params.get('end_label'), '台中市南區新和街1號');
  assert.strictEqual(params.get('via_label'), '中途點');
}

function testSnapTextKeepsGeocodeQualityVisible() {
  const ctx = loadRouteScript();
  ctx.STATE.startMeta = {
    label: '逢甲大學',
    source: 'osm_poi',
    quality_flag: 'osm_reference',
    resolved_by: 'autocomplete',
  };

  assert.strictEqual(ctx.pointStatusText('start', { distance_m: 2.4 }), '吸附 2m · OSM 地標');
}

testApplyResolvedPointStoresMetadata();
testShareUrlIncludesHumanLabels();
testSnapTextKeepsGeocodeQualityVisible();
console.log('geocode UI tests passed');
