<?php

namespace Drupal\endpoint_status\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\endpoint_status\EndpointStatusInterface;
use Drupal\endpoint_status\EndpointStatusProcessor;

/**
 * Defines the EndpointStatus entity.
 *
 * @ConfigEntityType(
 *   id = "endpoint_status",
 *   label = @Translation("Endpoint Status"),
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\endpoint_status\Controller\EndpointStatusListBuilder",
 *     "form" = {
 *       "add" = "Drupal\endpoint_status\Form\EndpointStatusForm",
 *       "edit" = "Drupal\endpoint_status\Form\EndpointStatusForm",
 *       "delete" = "Drupal\endpoint_status\Form\EndpointStatusDeleteForm",
 *     }
 *   },
 *   config_prefix = "endpoint_status",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uri" = "uri",
 *     "enabled" = "enabled",
 *     "status" = "status",
 *     "message" = "message",
 *     "email_subscribers" = "email_subscribers",
 *     "processor" = "processor",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *      "uri",
 *      "enabled",
 *      "email_subscribers",
 *      "processor",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/endpoint_status/{endpoint_status}",
 *     "delete-form" =
 *   "/admin/config/system/endpoint_status/{endpoint_status}/delete",
 *   }
 * )
 */
class EndpointStatus extends ConfigEntityBase implements EndpointStatusInterface {

  /**
   * The EndpointStatus ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The EndpointStatus label.
   *
   * @var string
   */
  protected $label;

  /**
   * The EndpointStatus uri.
   *
   * @var string
   */
  protected $uri;

  /**
   * The EndpointStatus enabled.
   *
   * @var boolean
   */
  protected $enabled;

  /**
   * The EndpointStatus status.
   *
   * @var string
   */
  protected $status;

  /**
   * The EndpointStatus message, provided by the processor.
   *
   * @var string
   */
  protected $message;

  /**
   * The EndpointStatus processor.
   *
   * @var EndpointStatusProcessor
   */
  protected $processor;


  /**
   * The EndpointStatus subscribers.
   *
   * @var string[]
   */
  protected $email_subscribers;
}
