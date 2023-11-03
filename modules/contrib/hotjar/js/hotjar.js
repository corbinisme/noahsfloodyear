/**
 * @file
 * Initialize HotJar on the page.
 */
(function (drupalSettings) {
  'use strict';

  if (!drupalSettings.hotjar) {
    return;
  }

  (function(h,o,t,j,a,r){
    h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
    h._hjSettings={hjid:drupalSettings.hotjar.account,hjsv:drupalSettings.hotjar.snippetVersion};
    a=o.getElementsByTagName('head')[0];
    r=o.createElement('script');r.async=1;
    r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
    a.appendChild(r);
  })(window, document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
})(drupalSettings);
