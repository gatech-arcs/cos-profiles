<?php

namespace Drupal\queue_order_definition_fixtures\Plugin\QueueWorker;

/**
 * Class WorkerD.
 *
 * Worker definition for testing purposes.
 *
 * @package Drupal\queue_order_definition_fixtures\Plugin\QueueWorker
 *
 * @QueueWorker(
 *   id="queue_order_worker_D",
 *   title="Test worker with '3' position",
 *   weight=-30,
 *   cron={"time" = 60}
 * )
 */
class WorkerD extends WorkerBase {}
