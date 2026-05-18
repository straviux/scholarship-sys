import pagedjsPolyfillSource from '../../../public/vendor/pagedjs/paged.polyfill.min.js?raw';

export const pagedjsPolyfillScript = pagedjsPolyfillSource.replace(/<\/script/gi, '<\\/script');