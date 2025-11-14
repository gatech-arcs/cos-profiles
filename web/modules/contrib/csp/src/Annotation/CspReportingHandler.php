<?php

namespace Drupal\csp\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a CSP Response Handler Annotation object.
 *
 * @Annotation
 */
class CspReportingHandler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The plugin label.
   *
   * @var string
   */
  public string $label;

  /**
   * The plugin description.
   *
   * @var string
   */
  public string $description;

}
