CONTENTS OF THIS FILE
---------------------

- INTRODUCTION
- REQUIREMENTS
- RECOMMENDED MODULES
- INSTALLATION
- CONFIGURATION

INTRODUCTION
------------

This module is a CKEditor 5 plugin for creating responsive tables using a button. The built-in CKEditor table button is not responsive.

This responsive table is accessible by default. The button provides controls to set the number of **Rows** and **Columns**. It also has a **Headers** dropdown, **Caption**, and a **Caption Visible?** checkbox.

Once the table is inserted into the editor, there are controls for inserting, deleting, splitting, and merging columns and rows, as well toggling the caption visibility.

This plugin is built based on the [CKEditor 5 plugin development starter template module](https://www.drupal.org/project/ckeditor5_dev).

REQUIREMENTS
------------

- Drupal 9.3 or greater.

RECOMMENDED MODULES
-------------------

- No extra modules are required.

INSTALLATION
------------

- Install as usual. See https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8 for more information.

CONFIGURATION
-------------

- Configure text formats by navigating to **Configuration > Content authoring > Text formats and editors**, or visiting `/admin/config/content/formats`.
- Click **Configure** next to a text format that has CKEditor 5 enabled as the **Text format** that you'd like to add the responsive table functionality to.
- Drag the Responsive Table button <img src="./icons/responsivetable.svg" alt="The responsive table icon" width="16" height="16"> from the **Available Buttons** area to the **Active toolbar** area.
- Click **Save configuration**.
