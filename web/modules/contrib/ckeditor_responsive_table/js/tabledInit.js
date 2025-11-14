(function tabledInit(drupalSettings) {
  'use strict';
  const {
    captionSide,
    characterThresholdLarge,
    characterThresholdSmall,
    failClass,
    tableSelector,
  } = drupalSettings.ckeditorResponsiveTable;
  document.querySelectorAll(tableSelector).forEach(
    (table) => new Tabled({
      captionSide,
      characterThresholdLarge,
      characterThresholdSmall,
      failClass,
      table,
    })
  );
})(window.drupalSettings);
