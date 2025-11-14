/**
 * @file
 * JavaScript for removable Active Filters.
 */

'use strict';

/**
 * Object for interacting with active filters.
 */
class ActiveFilters {
  constructor() {
    this.attribute = 'data-active-filter';
  }

  /**
   * Get all active filter instances contained in an HTML element.
   *
   * @param {Document|HTMLElement} html
   *
   * @return {NodeListOf<Element|HTMLElement>|HTMLElement[]}
   */
  getInstances(html) {
    if (html instanceof HTMLElement && html.matches(`[${this.attribute}s]`)) {
      return [html];
    }
    return html.querySelectorAll(`[${this.attribute}s]`);
  }

  /**
   * Get all removable active filters.
   *
   * @param {HTMLElement|NodeListOf<Element|HTMLElement>} activeFilters
   *
   * @return {NodeListOf<Element|HTMLButtonElement>|HTMLButtonElement[]}
   */
  getRemovable(activeFilters) {
    return activeFilters.querySelectorAll(`[${this.attribute}-removable]`);
  }

  /**
   * Get the clear all button.
   *
   * @param {HTMLElement|NodeListOf<Element|HTMLElement>} activeFilters
   *
   * @return {HTMLButtonElement|null}
   */
  getClearAll(activeFilters) {
    return activeFilters.querySelector(`[${this.attribute}s-clear-all]`);
  }

  /**
   * Get the name of the exposed filter the active filter represents.
   *
   * @param {HTMLButtonElement} activeFilter
   *
   * @return {string}
   */
  getName(activeFilter) {
    const name = activeFilter.getAttribute(`${this.attribute}-name`);
    return name || '';
  }

  /**
   * Get the value of the exposed filter the active filter represents.
   *
   * @param {HTMLButtonElement} activeFilter
   *
   * @return {string}
   */
  getValue(activeFilter) {
    const value = activeFilter.getAttribute(`${this.attribute}-value`);
    return value || '';
  }

  /**
   * Get the exposed filter element that the active filter is generated from.
   *
   * @param {HTMLButtonElement} activeFilter
   *
   * @return {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement|null}
   */
  getExposedFilter(activeFilter) {
    const view = ActiveFilters.findViewRoot(activeFilter);
    const name = this.getName(activeFilter);

    const single = view.querySelector(`[name="${name}"]`);
    if (single) {
      return single;
    }

    const multiple = view.querySelector(`[name="${name}[]"]`);
    if (multiple) {
      return multiple;
    }

    const value = this.getValue(activeFilter);
    const many = view.querySelector(`[name="${name}[${value}]"]`);
    if (many) {
      return many;
    }

    return null;
  }

  /**
   * Get the type of exposed filter the active filter is generated from.
   *
   * @param {HTMLButtonElement} activeFilter
   *
   * @return {string}
   */
  getType(activeFilter) {
    const exposedFilter = this.getExposedFilter(activeFilter);
    if (!exposedFilter) {
      return '';
    }
    switch (exposedFilter.nodeName.toLowerCase()) {
      case 'input':
        return exposedFilter.getAttribute('type');

      case 'select':
        return exposedFilter.hasAttribute('multiple')
          ? 'select_multiple'
          : 'select';

      default:
        return exposedFilter.nodeName.toLowerCase();
    }
  }

  /**
   * Remove an active filter by removing the value from its exposed filter.
   *
   * @param {HTMLButtonElement} activeFilter
   */
  remove(activeFilter) {
    const exposedFilter = this.getExposedFilter(activeFilter);
    const all = exposedFilter.parentNode.querySelector('[value="All"]');

    // Support additional input types by allowing exposed filters to provide
    // their own removal method.
    if (
      exposedFilter.hasOwnProperty('activeFilterRemove') &&
      typeof exposedFilter.activeFilterRemove === 'function'
    ) {
      exposedFilter.activeFilterRemove(activeFilter);
      return;
    }

    switch (this.getType(activeFilter)) {
      case 'checkbox':
        exposedFilter.checked = false;
        break;

      case 'radio':
        exposedFilter.checked = false;
        if (all) {
          all.checked = true;
        }
        break;

      case 'select':
        exposedFilter.value = exposedFilter.querySelector('[value="All"]')
          ? 'All'
          : exposedFilter.querySelector('option').value;
        break;

      case 'select_multiple':
        exposedFilter.querySelector(
          `[value="${this.getValue(activeFilter)}"]`,
        ).selected = false;
        break;

      default:
        exposedFilter.value = '';
        break;
    }
  }

  /**
   * Remove all active filters.
   *
   * @param {HTMLElement} activeFilter
   */
  removeAll(activeFilter) {
    const activeFilters = this.getRemovable(activeFilter);
    if (activeFilters) {
      activeFilters.forEach((filter) => {
        this.remove(filter);
      });
      ActiveFilters.submit(activeFilters.item(0));
    }
  }

  /**
   * Submit the exposed form associated with the active filter.
   *
   * @param {HTMLButtonElement} activeFilter
   */
  static submit(activeFilter) {
    const view = ActiveFilters.findViewRoot(activeFilter);
    const submitButton = view.querySelector('form [type="submit"]');
    if (submitButton) {
      submitButton.click();
    } else {
      view.querySelector('form').requestSubmit();
    }
  }

  /**
   * Attempt to find the root view container element for an active filter.
   *
   * @param {HTMLButtonElement} activeFilter
   *
   * @return {HTMLElement|Document}
   */
  static findViewRoot(activeFilter) {
    return (
      activeFilter.closest('.view, .views-element-container, main') || document
    );
  }
}

window.ActiveFilters = new ActiveFilters();

/**
 * Behaviors for Active Filters.
 */
((Drupal, once, filters) => {
  /**
   * Remove an individual active filter when clicked.
   */
  Drupal.behaviors.activeFiltersRemove = {
    attach: (context) => {
      once('active-filters-removable', filters.getInstances(context)).forEach(
        (activeFilter) => {
          filters.getRemovable(activeFilter).forEach((filter) => {
            filter.addEventListener('click', () => {
              filters.remove(filter);
              ActiveFilters.submit(filter);
            });
          });
        },
      );
    },
  };

  /**
   * Remove all active filters when the clear all button is clicked.
   */
  Drupal.behaviors.activeFiltersClearAll = {
    attach: (context) => {
      once('active-filters-clear-all', filters.getInstances(context)).forEach(
        (activeFilter) => {
          const clearAll = filters.getClearAll(activeFilter);
          if (clearAll) {
            clearAll.addEventListener('click', () => {
              filters.removeAll(activeFilter);
            });
          }
        },
      );
    },
  };
})(Drupal, once, window.ActiveFilters);
