/**
 * Updates the Fences preset data based on the selected preset.
 *
 * @param {HTMLSelectElement} presetSelect
 *   The select element for choosing the preset.
 * @param {HTMLElement} detailsWrapper
 *   The wrapper element of the fences fields.
 * @param {Object} drupalSettings
 *   The Drupal settings object.
 */
function updateFencesPresetData(presetSelect, detailsWrapper, drupalSettings) {
  if (presetSelect.value) {
    const selectedPreset = drupalSettings.fencesPresets[presetSelect.value];

    const [fieldTagElement] =
      detailsWrapper.getElementsByClassName('fences-field-tag');
    // Adjust the value of the field tag field:
    fieldTagElement.value = selectedPreset.field_tag;
    // Manually fire the change event to trigger states api conditions:
    fieldTagElement.dispatchEvent(new Event('change'));

    // Only adjust the value of the field classes field:
    const [fieldClassesElement] = detailsWrapper.getElementsByClassName(
      'fences-field-classes',
    );
    fieldClassesElement.value = selectedPreset.field_classes;

    const [fieldItemsWrapperTagElement] = detailsWrapper.getElementsByClassName(
      'fences-field-items-wrapper-tag',
    );
    fieldItemsWrapperTagElement.value = selectedPreset.field_items_wrapper_tag;
    fieldItemsWrapperTagElement.dispatchEvent(new Event('change'));

    const [fieldItemsWrapperClassesElement] =
      detailsWrapper.getElementsByClassName(
        'fences-field-items-wrapper-classes',
      );
    fieldItemsWrapperClassesElement.value =
      selectedPreset.field_items_wrapper_classes;

    const [fieldItemTagElement] = detailsWrapper.getElementsByClassName(
      'fences-field-item-tag',
    );
    fieldItemTagElement.value = selectedPreset.field_item_tag;
    fieldItemTagElement.dispatchEvent(new Event('change'));

    const [fieldItemClassesElement] = detailsWrapper.getElementsByClassName(
      'fences-field-item-classes',
    );
    fieldItemClassesElement.value = selectedPreset.field_item_classes;

    const [labelTagElement] =
      detailsWrapper.getElementsByClassName('fences-label-tag');
    labelTagElement.value = selectedPreset.label_tag;
    labelTagElement.dispatchEvent(new Event('change'));

    const [labelClassesElement] = detailsWrapper.getElementsByClassName(
      'fences-label-classes',
    );
    labelClassesElement.value = selectedPreset.label_classes;
  }
}

(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.fences_presets = {
    attach(context, settings) {
      once('fences-presets', '.fences-details-wrapper', context).forEach(
        (detailsWrapper) => {
          // Assign first and only collection entry to the presetSelect:
          const [presetSelect] = detailsWrapper.getElementsByClassName(
            'fences-preset-selector',
          );
          presetSelect.addEventListener('change', function () {
            updateFencesPresetData(
              presetSelect,
              detailsWrapper,
              drupalSettings,
            );
          });
        },
      );
    },
  };
})(Drupal, drupalSettings, once);
