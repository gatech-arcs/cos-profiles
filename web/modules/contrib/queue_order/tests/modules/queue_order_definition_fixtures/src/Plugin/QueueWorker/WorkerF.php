<?php

namespace Drupal\queue_order_definition_fixtures\Plugin\QueueWorker;

/**
 * Class WorkerF.
 *
 * Worker definition for testing purposes.
 *
 * @package Drupal\queue_order_definition_fixtures\Plugin\QueueWorker
 *
 * @QueueWorker(
 *   id="queue_order_worker_F",
 *   title="Test worker with 'last' position",
 *   weight = 10,
 *   cron={"time" = 60}
 * )
 */
class WorkerF extends WorkerBase {}
