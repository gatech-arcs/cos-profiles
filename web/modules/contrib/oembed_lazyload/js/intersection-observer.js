'use strict';

(function (Drupal, drupalSettings) {

  let observe = false;

  Drupal.behaviors.oembedLazyloadIntersectionObserver = {
    attach: function attach(context) {

      if (observe === false) {
        // Check for IntersectionObserver support.
        // @see https://github.com/w3c/IntersectionObserver/blob/7382ce7e834c57ea166081484bd8f8a6de84de04/polyfill/intersection-observer.js#L16
        const modern_support =
          'IntersectionObserver' in window &&
          'IntersectionObserverEntry' in window &&
          'intersectionRatio' in window.IntersectionObserverEntry.prototype;

        observe = (function () {
          if (modern_support) {
            // Create an intersection observer that swaps out the src attribute.

            const options = {};
            if (drupalSettings.intersectionObserverMargin) {
              options.rootMargin = drupalSettings.intersectionObserverMargin;
            }
            const observer = new IntersectionObserver(function (entries) {
              entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                  const target = entry.target;
                  const button = target.parentNode.querySelector('.oembed-lazyload__button');
                  button.classList.add('oembed-lazyload__button--loading');
                  if (target.getAttribute('data-src')) {
                    target.setAttribute('src', target.getAttribute('data-src'));
                    target.removeAttribute('data-src');
                  }
                  observer.unobserve(target);
                }
              });
            }, options);
            return function (iframe) {
              observer.observe(iframe);
            };
          }
          else {
            // Fall back to a supported, albeit inferior mechanism.
            return function (iframe) {
              function scroll() {
                const rect = iframe.getBoundingClientRect();
                const height = window.innerHeight || document.documentElement.clientHeight;
                const width = window.innerWidth || document.documentElement.clientWidth;
                if (rect.right >= 0 && rect.bottom >= 0 && rect.left <= width && rect.top <= height) {
                  const button = iframe.parentNode.querySelector('.oembed-lazyload__button');
                  button.classList.add('oembed-lazyload__button--loading');
                  if (iframe.getAttribute('data-src')) {
                    iframe.setAttribute('src', iframe.getAttribute('data-src'));
                    iframe.removeAttribute('data-src');
                  }
                  document.removeEventListener('scroll', scroll);
                }
              }
              document.addEventListener('scroll', scroll);

              // Account for above-the-fold videos.
              const event = document.createEvent('Event');
              event.initEvent('scroll', false, true);
              document.dispatchEvent(event);
            };
          }
        })();
      }

      // Set the observer to watch each eligible iframe.
      const videos = context.querySelectorAll('.oembed-lazyload[data-strategy="intersection-observer"]');
      for (let i = 0; i < videos.length; ++i) {
        const iframe = videos[i].querySelector('.oembed-lazyload__iframe');
        iframe.addEventListener('load', function() {
          // This is a cheap hack to ensure the user doesn't see a flash as the
          // iframe and placeholder are swapped out.  Setting the opacity to a
          // value < 1 will cause the iframe to have its own stacking context.
          // This effectively means that the iframe will stack on top of the
          // button element.
          //
          // @see https://www.w3.org/TR/css-color-3/#transparency
          //
          // Important: I am assuming there's an 8 bit alpha channel!  I cannot
          // find any white-paper specification that enforces this, but it does
          // seem like a logical conclusion, as the RGB channels are 8 bit.
          iframe.style.opacity = (1 - (1 / 256));
          iframe.classList.remove('oembed-lazyload__iframe--hidden');

          // There's a 200ms transition on opacity.  At the end, we can be sure
          // that the iframe has rendered its first frame with its new stacking
          // order.  We can now detach the button safely without a 'flash'.
          setTimeout(function() {
            const button = iframe.parentNode.querySelector('.oembed-lazyload__button');
            button.classList.add('oembed-lazyload__button--hidden');

            // Restore the original full opacity as well.
            iframe.style.opacity = 1;
          }, 200);
        });
        observe(iframe);
      }
    }
  };
})(Drupal, drupalSettings);
