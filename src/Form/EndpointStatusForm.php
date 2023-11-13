<?php

namespace Drupal\endpoint_status\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\endpoint_status\Constants\EndpointStatusConstants;
use Drupal\endpoint_status\EndpointStatusProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Endpoint Statusadd and edit forms.
 */
class EndpointStatusForm extends EntityForm {

  /**
   * The endpoint status processor plugin manager.
   *
   * We use this to get all of the endpoint status processor plugins.
   *
   * @var \Drupal\endpoint_status\EndpointStatusProcessorPluginManager
   */
  protected $endpointStatusProcessorPluginManager;


  /**
   * Constructs an EndpointStatusForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   *
   * @param \Drupal\endpoint_status\EndpointStatusProcessorPluginManager $endpoint_status_processor_manager
   *   The endpoint status processor plugin manager service. We're injecting
   *   this service so that we can use it to access the endpoint status
   *   processor plugins.
   *
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EndpointStatusProcessorPluginManager $endpoint_status_processor_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->endpointStatusProcessorPluginManager = $endpoint_status_processor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.endpoint_status_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $endpoint_status = $this->entity;

    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('Endpoint status information'),
      '#open' => TRUE,
    ];

    $form['status']['intro'] = [
      '#type' => 'item',
      '#markup' => $this->t('The current short status is @status', [
        '@status' => $endpoint_status->get('status') ?? EndpointStatusConstants::NEUTRAL,
      ]),
    ];

    $form['status']['outro'] = [
      '#type' => 'item',
      '#markup' => $this->t('@message', [
        '@message' => $endpoint_status->get('message') ?? $this->t('No detailed message exists. This endpoint is likely unprocessed.'),
      ]),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $endpoint_status->label(),
      '#description' => $this->t("Label for the EndpointStatus."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $endpoint_status->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$endpoint_status->isNew(),
    ];

    $form['uri'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#default_value' => $endpoint_status->get('uri'),
    ];

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $endpoint_status->get('enabled'),
      '#description' => $this->t('Enable to have this endpoint automatically processed on Cron.'),
    ];

    $email_subscribers = (array) $endpoint_status->get('email_subscribers');
    $email_subscribers = implode(',', array_values($email_subscribers));

    $form['email_subscribers'] = [
      '#type' => 'email',
      '#title' => $this->t('Subscribers'),
      '#default_value' => $email_subscribers,
      '#multiple' => TRUE,
      '#description' => $this->t('Comma-separated email addresses.'),
      '#maxlength' => NULL,
    ];

    $allProcessors = $this->endpointStatusProcessorPluginManager->getDefinitions();
    $processorOptions = array_reduce(
      $allProcessors,
      function ($carry, $item) {
        $id = $item['id'];
        $label = $item['label'];
        $carry[$id] = $label;
        return $carry;
      },
      []
    );

    $form['processor'] = [
      '#type' => 'select',
      '#title' => $this->t('Processor'),
      '#default_value' => $endpoint_status->get('processor') ?? 'default',
      '#description' => $this->t('The processor chosen for this endpoint.'),
      '#options' => $processorOptions,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $endpoint_status = $this->entity;
    $email_subscribers = $form_state->getValue('email_subscribers');
    $email_subscribers = explode(',', $email_subscribers);
    $endpoint_status->set('email_subscribers', $email_subscribers);
    $status = $endpoint_status->save();

    if ($status === SAVED_NEW) {
      $this->messenger()
        ->addMessage($this->t('The %label Endpoint Status created.', [
          '%label' => $endpoint_status->label(),
        ]));
    }
    else {
      $this->messenger()
        ->addMessage($this->t('The %label Endpoint Status updated.', [
          '%label' => $endpoint_status->label(),
        ]));
    }

    $form_state->setRedirect('entity.endpoint_status.collection');
  }

  /**
   * Helper function to check whether an Endpoint Statusconfiguration entity
   * exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('endpoint_status')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
