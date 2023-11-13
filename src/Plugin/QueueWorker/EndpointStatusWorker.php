<?php

namespace Drupal\endpoint_status\Plugin\QueueWorker;

/**
 * A report worker.
 *
 * @QueueWorker(
 *   id = "endpoint_status_queue",
 *   title = @Translation("Queue worker for endpoint_status"),
 *   cron = {"time" = 60}
 * )
 *
 */
class EndpointStatusWorker extends EndpointStatusWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($endpointStatus) {
    $processorId = $endpointStatus->get('processor') ?? 'default';
    /** @var \Drupal\endpoint_status\EndpointStatusProcessorInterface $processor */
    $processor = $this->endpointStatusProcessorPluginManager->createInstance($processorId);
    $processor->processEndpointStatus($endpointStatus);
    $this->reportWork(1, $endpointStatus);
  }
}
