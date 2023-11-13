<?php

namespace Drupal\endpoint_status;

use Drupal\endpoint_status\Entity\EndpointStatus;

/**
 * An interface for all EndpointStatusProcessor type plugins.
 *
 * When defining a new plugin type you need to define an interface that all
 * plugins of the new type will implement. This ensures that consumers of the
 * plugin type have a consistent way of accessing the plugin's functionality. It
 * should include access to any public properties, and methods for accomplishing
 * whatever business logic anyone accessing the plugin might want to use.
 *
 * For example, an image manipulation plugin might have a "process" method that
 * takes a known input, probably an image file, and returns the processed
 * version of the file.
 *
 * In our case we'll define methods for accessing the human readable description
 * of a endpoint status processor and the number of calories per serving.
 */
interface EndpointStatusProcessorInterface {

  /**
   * Provide a description of the endpoint status processor.
   *
   * @return string
   *   A string description of the endpoint status processor.
   */
  public function description();

  /**
   * Provide the id.
   *
   * @return string
   *   The id.
   */
  public function id();

  /**
   * Provide the label.
   *
   * @return string
   *   The label.
   */
  public function label();


  /**
   * Process an endpoint status configuration.
   *
   * @param \Drupal\endpoint_status\Entity\EndpointStatus $endpointStatus
   *   An EndpointStatus configuration object.
   */
  public function processEndpointStatus(EndpointStatus $endpointStatus);

}
