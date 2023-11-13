<?php

namespace Drupal\endpoint_status\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\endpoint_status\EndpointStatusProcessorPluginManager;

/**
 * Provides base functionality for the ReportWorkers.
 */
abstract class EndpointStatusWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The endpoint status processor plugin manager.
   *
   * We use this to get all of the endpoint status processor plugins.
   *
   * @var \Drupal\endpoint_status\EndpointStatusProcessorPluginManager
   */
  protected $endpointStatusProcessorPluginManager;

  /**
   * EndpointStatusWorkerBase constructor.
   *
   * @param array $configuration
   *   The configuration of the instance.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service the instance should use.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service the instance should use.
   * @param \Drupal\endpoint_status\EndpointStatusProcessorPluginManager $endpoint_status_processor_manager
   *   The endpoint status processor plugin manager service. We're injecting
   *   this service so that we can use it to access the endpoint status
   *   processor plugins.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, LoggerChannelFactoryInterface $logger, EndpointStatusProcessorPluginManager $endpoint_status_processor_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->logger = $logger;
    $this->endpointStatusProcessorPluginManager = $endpoint_status_processor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $form = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.endpoint_status_processor')
    );
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * Simple reporter log and display information about the queue.
   *
   * @param int $worker
   *   Worker number.
   * @param \Drupal\endpoint_status\EndpointStatusInterface $item
   *   The $item which was stored in the cron queue.
   */
  protected function reportWork($worker, $item) {
    if ($this->state->get('endpoint_status_show_status_message')) {
      $this->messenger()->addMessage(
        $this->t('Queue @worker worker processed item with id @id and label @label', [
          '@worker' => $worker,
          '@id' => $item->id(),
          '@label' => $item->label(),
        ])
      );
    }
    $this->logger->get('endpoint_status')->info('Queue @worker worker processed item with id @id and label @label', [
      '@worker' => $worker,
      '@id' => $item->id(),
      '@label' => $item->label(),
    ]);
  }

}
