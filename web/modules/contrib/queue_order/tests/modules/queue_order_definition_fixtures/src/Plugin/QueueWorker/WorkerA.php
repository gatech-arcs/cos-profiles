<?php

namespace Drupal\queue_order_definition_fixtures\Plugin\QueueWorker;

/**
 * Class WorkerA.
 *
 * Worker definition for testing purposes.
 *
 * @package Drupal\queue_order_definition_fixtures\Plugin\QueueWorker
 *
 * @QueueWorker(
 *   id="queue_order_worker_A",
 *   title="Test worker with '2' position",
 *   weight=-40,
 *   cron={"time" = 60}
 * )
 */
class WorkerA extends WorkerBase {}
