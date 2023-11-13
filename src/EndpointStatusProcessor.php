<?php

namespace Drupal\endpoint_status;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * A base class to help developers implement their own endpoint status processor plugins.
 *
 * This is a helper class which makes it easier for other developers to
 * implement endpoint status processor plugins in their own modules. In EndpointStatusProcessor we provide
 * some generic methods for handling tasks that are common to pretty much all
 * endpoint status processor plugins. Thereby reducing the amount of boilerplate code required to
 * implement a endpoint status processor plugin.
 *
 * In this case both the description and calories properties can be read from
 * the @EndpointStatusProcessor annotation. In most cases it is probably fine to just use that
 * value without any additional processing. However, if an individual plugin
 * needed to provide special handling around either of these things it could
 * just override the method in that class definition for that plugin.
 *
 * We intentionally declare our base class as abstract, and don't implement the
 * order() method required by \Drupal\endpoint_status\EndpointStatusProcessorInterface.
 * This way even if they are using our base class, developers will always be
 * required to define an order() method for their custom endpoint status processor type.
 *
 * @see \Drupal\endpoint_status\Annotation\EndpointStatusProcessor
 * @see \Drupal\endpoint_status\EndpointStatusProcessorInterface
 */
abstract class EndpointStatusProcessor extends PluginBase implements EndpointStatusProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function description() {
    // Retrieve the @description property from the annotation and return it.
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    // Retrieve the @id property from the annotation and return it.
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Retrieve the @id property from the annotation and return it.
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function processEndpointStatus($endpointStatus);

}
