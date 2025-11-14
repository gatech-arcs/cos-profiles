((Drupal, once) => {
  Drupal.behaviors.diffPlusUi = {
    attach: (context) => {
      const settingsToggles = once(
        'diffPlusUi',
        '.diff-plus-ui__settings-toggle',
        context,
      );
      settingsToggles.forEach((settingsToggle) => {
        settingsToggle.addEventListener('click', () => {
          settingsToggle.setAttribute(
            'aria-expanded',
            settingsToggle.getAttribute('aria-expanded') === 'true'
              ? 'false'
              : 'true',
          );
        });
      });
    },
  };
})(Drupal, once);
