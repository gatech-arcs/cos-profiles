<?php

namespace Drupal\queue_order_definition_fixtures\Plugin\QueueWorker;

/**
 * Class WorkerE.
 *
 * Worker definition for testing purposes.
 *
 * @package Drupal\queue_order_definition_fixtures\Plugin\QueueWorker
 *
 * @QueueWorker(
 *   id="queue_order_worker_E",
 *   title="Test worker with '4' position",
 *   weight=-20,
 *   cron={"time" = 60}
 * )
 */
class WorkerE extends WorkerBase {}
