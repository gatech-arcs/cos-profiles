# Twig Casings

## Overview

Have you ever been in Twig and thought: "I just want a filter to make my string camelCase, kebab-case, MACRO_CASE, PascalCase, snake_case, or Train-Case?" This module allows you to pass any string with spaces, dashes, underscores, or even messy input with special characters, and it will normalise and case your string correctly.

## Features

The available filters are:

| Filter        | Input Example                                                                                   | Output Example                                                                                   |
|---------------|--------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------|
| **camel_case** | `{{ 'Drupal is without-doubt the best CMS_there_is'\|camel_case }}`                          | `drupalIsWithoutDoubtTheBestCmsThereIs`                                                          |
| **kebab_case** | `{{ 'Drupal is without-doubt the best CMS_there_is'\|kebab_case }}`                          | `drupal-is-without-doubt-the-best-cms-there-is`                                                  |
| **macro_case** | `{{ 'Drupal is without-doubt the best CMS_there_is'\|macro_case }}`                          | `DRUPAL_IS_WITHOUT_DOUBT_THE_BEST_CMS_THERE_IS`                                                  |
| **pascal_case** | `{{ 'Drupal is without-doubt the best CMS_there_is'\|pascal_case }}`                          | `DrupalIsWithoutDoubtTheBestCmsThereIs`                                                         |
| **snake_case** | `{{ 'Drupal is without-doubt the best CMS_there_is'\|snake_case }}`                          | `drupal_is_without_doubt_the_best_cms_there_is`                                                  |
| **train_case** | `{{ 'Drupal is without-doubt the best CMS_there_is'\|train_case }}`                          | `Drupal-Is-Without-Doubt-The-Best-Cms-There-Is`                                                  |

## String Normalisation

Before applying any casing, the module automatically **normalises your string** by:

- **Replacing any non-alphanumeric character** (like `!`, `@`, `#`, `_`, `-`, `*`, etc.) with a space.
- **Trimming leading and trailing spaces.**
- **Reducing multiple spaces to a single space.**

### Example of Normalisation

Messy input:
```twig
{{ '   --This___is   a_MESSY_text!!'|snake_case }}
```

Will output:
```text
this_is_a_messy_text
```

This behaviour ensures your output is always clean regardless of the input format.

## Why This Matters

The normalisation process is especially useful when dealing with:

- **User-generated content** (like form submissions or content imports).
- **Data from third-party APIs** that may have unpredictable formatting.
- **Content with mixed separators** (spaces, underscores, dashes).

## Usage Examples

Here are some real-world use cases in a Drupal Twig template:

### Converting form input to camelCase
```twig
<label for="username">Username</label>
<input id="username" value="{{ node.field_username.value|camel_case }}" />
```

Input: `john-doe_from-nyc`
Output: `johnDoeFromNyc`

---

### Creating CSS class names with kebab-case
```twig
<div class="section {{ node.field_category.value|kebab_case }}">
  {{ node.field_title.value }}
</div>
```

Input: `Latest News & Updates`
Output: `latest-news-updates`

---

### Generating constant names with MACRO_CASE
```twig
const {{ node.field_variable_name.value|macro_case }} = '{{ node.field_value.value }}';
```

Input: `api key`
Output: `API_KEY`

## Gotchas & Edge Cases

- **Numbers remain untouched.** For example, `Order 123` becomes `order123` in camelCase, not `order-123`.
- **Special characters are stripped.** `$%&` becomes an empty space.
- **Empty input results in empty output.** If no text is provided, the filter will return an empty string.

## Requirements

- A Drupal >=10 installation.
- A custom or existing theme to use the Twig filters.

## Installation

Install using Composer:

```bash
composer require drupal/twig_casings
```

Enable the module in the Drupal UI or via Drush:

```bash
drush en twig_casings
```

## Support

If you encounter any issues or have questions about the module, please feel free to submit a support request or contribute on the project's repository.
