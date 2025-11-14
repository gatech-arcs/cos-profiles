<?php

namespace Drupal\queue_order_definition_fixtures\Plugin\QueueWorker;

/**
 * Class WorkerC.
 *
 * Worker definition for testing purposes.
 *
 * @package Drupal\queue_order_definition_fixtures\Plugin\QueueWorker
 *
 * @QueueWorker(
 *   id="queue_order_worker_C",
 *   title="Test worker with '5' position",
 *   weight=-10,
 *   cron={"time" = 60}
 * )
 */
class WorkerC extends WorkerBase {}
