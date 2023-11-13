<?php

namespace Drupal\endpoint_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\endpoint_status\EndpointStatusProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for our example pages.
 */
class EndpointStatusProcessorPluginTypeController extends ControllerBase {

  /**
   * The endpoint status processor plugin manager.
   *
   * We use this to get all of the endpoint status processor plugins.
   *
   * @var \Drupal\endpoint_status\EndpointStatusProcessorPluginManager
   */
  protected $endpointStatusProcessorPluginManager;

  /**
   * Constructor.
   *
   * @param \Drupal\endpoint_status\EndpointStatusProcessorPluginManager $endpoint_status_processor_manager
   *   The endpoint status processor plugin manager service. We're injecting this service so that
   *   we can use it to access the endpoint status processor plugins.
   */
  public function __construct(EndpointStatusProcessorPluginManager $endpoint_status_processor_manager) {
    $this->endpointStatusProcessorPluginManager = $endpoint_status_processor_manager;
  }

  /**
   * Displays a page with an overview of our plugin type and plugins.
   *
   * Lists all the EndpointStatusProcessor plugin definitions by using methods on the
   * \Drupal\endpoint_status\EndpointStatusProcessorPluginManager class. Lists out the
   * description for each plugin found by invoking methods defined on the
   * plugins themselves. You can find the plugins we have defined in the
   * \Drupal\endpoint_status\Plugin\EndpointStatusProcessor namespace.
   *
   * @return array
   *   Render API array with content for the page at
   *   /examples/endpoint_status.
   */
  public function description() {
    $build = [];

    $build['intro'] = [
      '#markup' => $this->t("This page lists the endpoint status processor plugins we've created. The endpoint status processor plugin type is defined in Drupal\\endpoint_status\\EndpointStatusProcessorPluginManager. The various plugins are defined in the Drupal\\endpoint_status\\Plugin\\EndpointStatusProcessor namespace."),
    ];

    // Get the list of all the endpoint status processor plugins defined on the system from the
    // plugin manager. Note that at this point, what we have is *definitions* of
    // plugins, not the plugins themselves.
    $endpoint_status_processor_plugin_definitions = $this->endpointStatusProcessorPluginManager->getDefinitions();

    // Let's output a list of the plugin definitions we now have.
    $items = [];
    foreach ($endpoint_status_processor_plugin_definitions as $endpoint_status_processor_plugin_definition) {
      // Here we use various properties from the plugin definition. These values
      // are defined in the annotation at the top of the plugin class: see
      // \Drupal\endpoint_status\Plugin\EndpointStatusProcessor\DefaultEndpointStatusProcessor.
      $items[] = $this->t("@id ( label: @label, description: @description )", [
        '@id' => $endpoint_status_processor_plugin_definition['id'],
        '@label' => $endpoint_status_processor_plugin_definition['label'],
        '@description' => $endpoint_status_processor_plugin_definition['description'],
      ]);
    }

    // Add our list to the render array.
    $build['plugin_definitions'] = [
      '#theme' => 'item_list',
      '#title' => 'EndpointStatusProcessor plugin definitions',
      '#items' => $items,
    ];

    // To get an instance of a plugin, we call createInstance() on the plugin
    // manager, passing the ID of the plugin we want to load. Let's output a
    // list of the plugins by loading an instance of each plugin definition and
    // collecting the description from each.
    $items = [];
    // The array of plugin definitions is keyed by plugin id, so we can just use
    // that to load our plugin instances.
    foreach ($endpoint_status_processor_plugin_definitions as $plugin_id => $endpoint_status_processor_plugin_definition) {
      // We now have a plugin instance. From here on it can be treated just as
      // any other object; have its properties examined, methods called, etc.
      $plugin = $this->endpointStatusProcessorPluginManager->createInstance($plugin_id);
      $items[] = $plugin->description();
    }

    $build['plugins'] = [
      '#theme' => 'item_list',
      '#title' => 'EndpointStatusProcessor plugins',
      '#items' => $items,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Override the parent method so that we can inject our endpoint status processor plugin
   * manager service into the controller.
   *
   * For more about how dependency injection works read
   * https://www.drupal.org/node/2133171
   *
   * @see container
   */
  public static function create(ContainerInterface $container) {
    // Inject the plugin.manager.endpoint_status_processor service that represents our plugin
    // manager as defined in the endpoint_status.services.yml file.
    return new static($container->get('plugin.manager.endpoint_status_processor'));
  }

}
