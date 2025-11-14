((Drupal, drupalSettings) => {
  Drupal.behaviors.diffPlusRawHtml = {
    attach: (context) => {
      const diffPreview = context.querySelector('[data-diff-plus-preview]');
      if (diffPreview) {
        document.getElementsByTagName('head')[0].appendChild(
          Object.assign(document.createElement('link'), {
            rel: 'stylesheet',
            href: `https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/${drupalSettings.diff_plus.highlight_style}`,
          }),
        );

        const leftText = html_beautify(
          drupalSettings.diff_plus.left_revision,
          drupalSettings.diff_plus.beautify_options,
        );
        const rightText = html_beautify(
          drupalSettings.diff_plus.right_revision,
          drupalSettings.diff_plus.beautify_options,
        );

        const patch = Diff.createPatch(
          drupalSettings.diff_plus.diff_name,
          leftText,
          rightText,
        );
        const diffUI = new Diff2HtmlUI(
          diffPreview,
          patch,
          drupalSettings.diff_plus.ui_options,
        );

        diffUI.draw();
        diffUI.highlightCode();
      }
    },
  };
})(Drupal, drupalSettings);
