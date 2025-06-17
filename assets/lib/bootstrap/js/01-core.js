/*!
 * Bootstrap v5.3.0 Core Functions
 * Event handling, utilities, and base classes
 */

// Data storage for component instances
const componentDataMap = new Map;

const Data = {
  set(element, key, instance) {
    if (!componentDataMap.has(element)) {
      componentDataMap.set(element, new Map);
    }
    const instanceMap = componentDataMap.get(element);
    if (!instanceMap.has(key) || instanceMap.size === 0) {
      instanceMap.set(key, instance);
    } else {
      console.error(`Bootstrap doesn't allow more than one instance per element. Bound instance: ${Array.from(instanceMap.keys())[0]}.`);
    }
  },
  get: (element, key) => componentDataMap.has(element) && componentDataMap.get(element).get(key) || null,
  remove(element, key) {
    if (!componentDataMap.has(element)) return;
    const instanceMap = componentDataMap.get(element);
    instanceMap.delete(key);
    if (instanceMap.size === 0) {
      componentDataMap.delete(element);
    }
  }
};

// Constants
const TRANSITION_END = 'transitionend';

// Utility functions
const toType = object => {
  if (object === null || object === undefined) {
    return `${object}`;
  }
  return Object.prototype.toString.call(object).match(/\s([a-z]+)/i)[1].toLowerCase();
};

const getSelector = element => {
  let selector = element.getAttribute('data-bs-target');
  if (!selector || selector === '#') {
    let hrefAttribute = element.getAttribute('href');
    if (!hrefAttribute || (!hrefAttribute.includes('#') && !hrefAttribute.startsWith('.'))) {
      return null;
    }
    if (hrefAttribute.includes('#') && !hrefAttribute.startsWith('#')) {
      hrefAttribute = `#${hrefAttribute.split('#')[1]}`;
    }
    selector = hrefAttribute && hrefAttribute !== '#' ? hrefAttribute.trim() : null;
  }
  return parseSelector(selector);
};

const parseSelector = selector => {
  if (selector && window.CSS && window.CSS.escape) {
    selector = selector.replace(/#([^\s"#']+)/g, (match, id) => `#${CSS.escape(id)}`);
  }
  return selector;
};

const triggerTransitionEnd = element => {
  element.dispatchEvent(new Event(TRANSITION_END));
};

const isElement = object => {
  if (!object || typeof object !== 'object') {
    return false;
  }
  if (typeof object.jquery !== 'undefined') {
    object = object[0];
  }
  return typeof object.nodeType !== 'undefined';
};

const getElement = object => {
  if (isElement(object)) {
    return object.jquery ? object[0] : object;
  }
  if (typeof object === 'string' && object.length > 0) {
    return document.querySelector(parseSelector(object));
  }
  return null;
};

const isVisible = element => {
  if (!isElement(element) || element.getClientRects().length === 0) {
    return false;
  }
  const elementIsVisible = getComputedStyle(element).getPropertyValue('visibility') === 'visible';
  const closedDetails = element.closest('details:not([open])');
  if (!closedDetails) {
    return elementIsVisible;
  }
  if (closedDetails !== element) {
    const summary = element.closest('summary');
    if (summary && summary.parentNode !== closedDetails) {
      return false;
    }
    if (summary === null) {
      return false;
    }
  }
  return elementIsVisible;
};

const isDisabled = element => {
  if (!element || element.nodeType !== Node.ELEMENT_NODE) {
    return true;
  }
  if (element.classList.contains('disabled')) {
    return true;
  }
  if (typeof element.disabled !== 'undefined') {
    return element.disabled;
  }
  return element.hasAttribute('disabled') && element.getAttribute('disabled') !== 'false';
};

const findShadowRoot = element => {
  if (!document.documentElement.attachShadow) {
    return null;
  }
  if (typeof element.getRootNode === 'function') {
    const root = element.getRootNode();
    return root instanceof ShadowRoot ? root : null;
  }
  if (element instanceof ShadowRoot) {
    return element;
  }
  if (!element.parentNode) {
    return null;
  }
  return findShadowRoot(element.parentNode);
};

const noop = () => {};

const reflow = element => {
  element.offsetHeight;
};

const getjQuery = () => {
  if (window.jQuery && !document.body.hasAttribute('data-bs-no-jquery')) {
    return window.jQuery;
  }
  return null;
};

const DOMContentLoadedCallbacks = [];

const isRTL = () => document.documentElement.dir === 'rtl';

const defineJQueryPlugin = plugin => {
  const callback = () => {
    const $ = getjQuery();
    if ($) {
      const name = plugin.NAME;
      const jQueryInterface = $.fn[name];
      $.fn[name] = plugin.jQueryInterface;
      $.fn[name].Constructor = plugin;
      $.fn[name].noConflict = () => {
        $.fn[name] = jQueryInterface;
        return plugin.jQueryInterface;
      };
    }
  };
  
  if (document.readyState === 'loading') {
    if (!DOMContentLoadedCallbacks.length) {
      document.addEventListener('DOMContentLoaded', () => {
        for (const callback of DOMContentLoadedCallbacks) {
          callback();
        }
      });
    }
    DOMContentLoadedCallbacks.push(callback);
  } else {
    callback();
  }
};

const execute = (possibleCallback, args = [], defaultValue = possibleCallback) => {
  return typeof possibleCallback === 'function' ? possibleCallback(...args) : defaultValue;
};

const executeAfterTransition = (callback, transitionElement, waitForTransition = true) => {
  if (!waitForTransition) {
    execute(callback);
    return;
  }
  
  const durationPadding = 5;
  const emulatedDuration = getTransitionDurationFromElement(transitionElement) + durationPadding;
  let called = false;
  
  const handler = ({target}) => {
    if (target !== transitionElement) {
      return;
    }
    called = true;
    transitionElement.removeEventListener(TRANSITION_END, handler);
    execute(callback);
  };
  
  transitionElement.addEventListener(TRANSITION_END, handler);
  setTimeout(() => {
    if (!called) {
      triggerTransitionEnd(transitionElement);
    }
  }, emulatedDuration);
};

const getTransitionDurationFromElement = element => {
  if (!element) {
    return 0;
  }
  
  let {transitionDuration, transitionDelay} = window.getComputedStyle(element);
  const floatTransitionDuration = Number.parseFloat(transitionDuration);
  const floatTransitionDelay = Number.parseFloat(transitionDelay);
  
  if (!floatTransitionDuration && !floatTransitionDelay) {
    return 0;
  }
  
  transitionDuration = transitionDuration.split(',')[0];
  transitionDelay = transitionDelay.split(',')[0];
  
  return (Number.parseFloat(transitionDuration) + Number.parseFloat(transitionDelay)) * 1000;
};

const getNextActiveElement = (list, activeElement, shouldGetNext, isCycleAllowed) => {
  const listLength = list.length;
  let index = list.indexOf(activeElement);
  
  if (index === -1) {
    return !shouldGetNext && isCycleAllowed ? list[listLength - 1] : list[0];
  }
  
  index += shouldGetNext ? 1 : -1;
  
  if (isCycleAllowed) {
    index = (index + listLength) % listLength;
  }
  
  return list[Math.max(0, Math.min(index, listLength - 1))];
};

// Export core utilities
window.BootstrapCore = {
  Data,
  TRANSITION_END,
  toType,
  getSelector,
  parseSelector,
  triggerTransitionEnd,
  isElement,
  getElement,
  isVisible,
  isDisabled,
  findShadowRoot,
  noop,
  reflow,
  getjQuery,
  isRTL,
  defineJQueryPlugin,
  execute,
  executeAfterTransition,
  getTransitionDurationFromElement,
  getNextActiveElement
};
