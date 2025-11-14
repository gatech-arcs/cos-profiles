# Active Filters

Active Filters provides a views area plugin which generates active filters from 
exposed filter selections. Active filters can be cleared individually or all at
once.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/active_filters).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/search/active_filters).

## Requirements

This module requires no modules outside of Drupal core.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

Active filters can be enabled on views by adding "Active Filters" to a view's
header or footer area. All options are contained in the area's configuration 
form.

### Options Per-Active Filters Set

- **Heading Text**: Sets the heading text output before the active filters.
- **Visually hide heading**: Adds the `visually-hidden` class to the heading 
  text element.
- **Group active filters by exposed filter**: Outputs active filters in separate
  groupings by exposed filter. When this option is enabled the heading text is 
  output before all active filters, rather than inline.
- **Clear All Button Text**: Sets the text of the clear all button. Leave empty
  to omit a clear all button.

### Options Per-Exposed Filter

- **Generate active filters**: Enable or disable active filters for the exposed
  filter.
- **Active filters can be removed individually**: Enable or disable the ability
  for users to click on active filters to remove them.
- **Rewrite active filter values**: Rewrites values on active filters. This does
  not rewrite the values in the exposed filters. Rewrites can be used to disable
  active filter generation of select values using the pattern `Original|`. This 
  can be used to display only one value for boolean exposed filters, for 
  example.

### Theming

Active filters includes a minimal amount of CSS. Active filter templates have
theme suggestions added for view IDs and display IDs, and for exposed filter 
names and values. Active filters are fully themeable, but the 
`data-active-filter*` attributes must be present for removable active filters
to work properly.

Active filters can be altered before rendering with `hook_active_filters_alter`.

### Additional Input Types

Active filters supports all HTML input types. Inputs modified by JavaScript 
libraries may require additional code to remove. Active filters provides a way
to define custom the removal behaviors per-exposed filter input element. If the
input has an `activeFilterRemove` method active filters calls the method to
remove the active filter value. The method is passed the active filter 
`HTMLButtonElement` that triggered the removal.

Example implementation:

```js
Drupal.behaviors.activeFilterCustomRemove = {
  attach: (context) => {
    const inputs = once('remove', '[name="input"]', context);
    inputs.forEach(input => {
      input.activeFiltersRemove = (activeFilter) => {
        // Custom removal logic.
      };
    });
  },
};
```

## Maintainers

Current maintainer:
- [Benjamin Baird (benabaird)](https://www.drupal.org/u/benabaird)
- [Sue Brassard (slbrassard)](https://www.drupal.org/u/slbrassard)
