/* Portions based upon https://github.com/afeld/bootstrap-toc */

(function($, Drupal) {
  "use strict";

  function init(root_elements, anchor_elements, showLinks = true, linkContent = '#') {

    let selector = '';
    for (var i = 0; i < root_elements.length; i++) {
      for (var j = 0; j < anchor_elements.length; j++) {
        selector += root_elements[i] + ' ' + anchor_elements[j] + ', ';
      }
    }
    selector = selector.replace(/,\s*$/, "");

    $(selector).each(function() {
      var anchor = generateAnchor(this);
      if (showLinks) {
        generateAnchorLink(this, anchor, linkContent);
      }
    });
  }

  function generateUniqueIdBase(el) {
    var text = $(el).text();

    // Adapted from
    // https://github.com/bryanbraun/anchorjs/blob/65fede08d0e4a705f72f1e7e6284f643d5ad3cf3/anchor.js#L237-L257

    // Regex for finding the non-safe URL characters (many need escaping): & +$,:;=?@"#{}|^~[`%!'<>]./()*\ (newlines, tabs, backspace, & vertical tabs)
    var nonsafeChars = /[& +$,:;=?@"#{}|^~[`%!'<>\]\.\/\(\)\*\\\n\t\b\v]/g,
      urlText;

    // Note: we trim hyphens after truncating because truncating can cause dangling hyphens.
    // Example string:
    // " ⚡⚡ Don't forget: URL fragments should be i18n-friendly, hyphenated, short, and clean."
    urlText = text
      .trim() // "⚡⚡ Don't forget: URL fragments should be i18n-friendly, hyphenated, short, and clean."
      .replace(/\'/gi, "") // "⚡⚡ Dont forget: URL fragments should be i18n-friendly, hyphenated, short, and clean."
      .replace(nonsafeChars, "-") // "⚡⚡-Dont-forget--URL-fragments-should-be-i18n-friendly--hyphenated--short--and-clean-"
      .replace(/-{2,}/g, "-") // "⚡⚡-Dont-forget-URL-fragments-should-be-i18n-friendly-hyphenated-short-and-clean-"
      .substring(0, 64) // "⚡⚡-Dont-forget-URL-fragments-should-be-i18n-friendly-hyphenated-"
      .replace(/^-+|-+$/gm, "") // "⚡⚡-Dont-forget-URL-fragments-should-be-i18n-friendly-hyphenated"
      .toLowerCase(); // "⚡⚡-dont-forget-url-fragments-should-be-i18n-friendly-hyphenated"

    return urlText || el.tagName.toLowerCase();
  }

  function generateUniqueId(el) {
    var anchorBase = generateUniqueIdBase(el);
    for (var i = 0;; i++) {
      var anchor = anchorBase;
      if (i > 0) {
        // Add suffix
        anchor += "-" + i;
      }
      // Check if ID already exists
      if (!document.getElementById(anchor)) {
        return anchor;
      }
    }
  }

  function generateAnchor(el) {
    if (el.id) {
      return el.id;
    } else {
      var anchor = generateUniqueId(el);
      el.id = anchor;
      return anchor;
    }
  }

  function generateAnchorLink(el, anchor, linkContent) {
    $(el).append('<a class="auto-anchor" title="Permalink to this item" href="#' + anchor + '">' + linkContent + '</a>');
  }


  $(window).on('load', function() {

    // Root elements to generate automatic ID's within.
    let roots = drupalSettings.auto_anchors.root_elements.split(',');
    // List of elements within the root elements to generate automatic ID's on.
    let elements = drupalSettings.auto_anchors.anchor_elements.split(',');

    init(
      roots,
      elements,
      drupalSettings.auto_anchors.show_automatic_anchor_links,
      drupalSettings.auto_anchors.link_content
    );

    if (window.location.hash && $(window.location.hash).length) {
      $('html,body').animate({ scrollTop: $(window.location.hash).offset().top }, 'slow');
    }
  });

})(jQuery, Drupal);
