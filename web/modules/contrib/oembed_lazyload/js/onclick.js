'use strict';

(function (Drupal) {
  Drupal.behaviors.oembedLazyload = {
    attach: function attach(context) {
      var video_embeds = context.querySelectorAll('.oembed-lazyload[data-strategy="onclick"]');
      for(let i = 0; i < video_embeds.length; ++i) {
        const video_embed = video_embeds[i];
        const button = video_embed.querySelector('.oembed-lazyload__button');

        const click = function() {
          var iframe = video_embed.querySelector('.oembed-lazyload__iframe');

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

          var src = iframe.getAttribute('data-src');
          if (src) {
            iframe.removeAttribute('data-src');
            iframe.setAttribute('src', src);
            button.classList.add('oembed-lazyload__button--loading');
            button.removeEventListener('click', click);
          }
          return false;
        };

        button.addEventListener('click', click);
      }
    }
  };
})(Drupal);
