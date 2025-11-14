CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Get the additional advantage of Queue API of Drupal core. The module provides
the functionality of sorting queue workers' definitions. That causes an effect
on queue execution order during the cron run. This tiny module allows control
execution order of defined queue workers by the Cron handler.

 * For a full description of the module, visit:
   https://www.drupal.org/project/queue_order

 * To submit bug reports and feature suggestions, or to track changes, visit:
   https://www.drupal.org/project/issues/queue_order


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

INSTALLATION
------------

 * Install the Queue order module as you typically install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

All weight values of queue workers are stored in the order property
of `queue_order.settings` config object. It contains a key - a value array,
where the key is the id of the queue worker, and the value - is the weight
value.

```yaml
# Example of queue_order.settings config object:
order:
  queue_worker_1: 0
  queue_worker_2: -1
  queue_worker_3: 1
  queue_worker_4: 2
```

UI
--

This module provides functionality without any admin UI. It should be
helpful in production. Use [Queue UI](https://www.drupal.org/project/queue_ui)
for development needs.


MAINTAINERS
-----------

[//]: # "cspell:disable-next-line"
 * Oleh Vehera (voleger) - https://www.drupal.org/u/voleger

Supporting organization:

[//]: # "cspell:disable-next-line"
 * GOLEMS GABB - https://www.drupal.org/golems-gabb

[//]: # "cspell:disable-next-line"
GOLEMS GABB is a team of experienced developers!
Our work includes strategy, design, and development
across a variety of industries.
