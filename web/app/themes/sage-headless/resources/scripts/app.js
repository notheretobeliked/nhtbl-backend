import domReady from '@roots/sage/client/dom-ready';
import Alpine from 'alpinejs'
import masonry from 'alpinejs-masonry'


/**
 * Application entrypoint
 */
domReady(async () => {
  Alpine.plugin(masonry)

  Alpine.start()
});

/**
 * @see {@link https://webpack.js.org/api/hot-module-replacement/}
 */
if (import.meta.webpackHot) import.meta.webpackHot.accept(console.error);
