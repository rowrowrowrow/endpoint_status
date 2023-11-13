<?php

namespace Drupal\endpoint_status\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a EndpointStatusProcessor annotation object.
 *
 * Provides an example of how to define a new annotation type for use in
 * defining a plugin type. Demonstrates documenting the various properties that
 * can be used in annotations for plugins of this type.
 *
 * Note that the "@ Annotation" line below is required and should be the last
 * line in the docblock. It's used for discovery of Annotation definitions.
 *
 * @see \Drupal\endpoint_status\EndpointStatusProcessorPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class EndpointStatusProcessor extends Plugin {
  /**
   * A brief, human readable, description of the endpoint status processor type.
   *
   * This property is designated as being translatable because it will appear
   * in the user interface. This provides a hint to other developers that they
   * should use the Translation() construct in their annotation when declaring
   * this property.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;


  /**
   * The unique ID of the endpoint status processor implementation.
   *
   * @var string
   */
  public $id;

  /**
   * The human readable name of the endpoint status processor implementation.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
