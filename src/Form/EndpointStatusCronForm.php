<?php

namespace Drupal\endpoint_status\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form with examples on how to use cron.
 */
class EndpointStatusCronForm extends ConfigFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs new EndpointStatusCronForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\CronInterface $cron
   *   The cron service.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue collection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, CronInterface $cron, QueueFactory $queue, StateInterface $state, TimeInterface $time) {
    parent::__construct($config_factory);
    $this->currentUser = $current_user;
    $this->cron = $cron;
    $this->queue = $queue;
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('cron'),
      $container->get('queue'),
      $container->get('state'),
      $container->get('datetime.time')
    );
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'endpoint_status';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('endpoint_status.settings');

    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('Cron status information'),
      '#open' => TRUE,
    ];
    $form['status']['intro'] = [
      '#type' => 'item',
      '#markup' => $this->t('The Endpoint Status Cron demonstrates hook_cron() and hook_queue_info() processing. If you have administrative privileges you can run cron from this page and see the results.'),
    ];

    $next_execution = $this->state->get('endpoint_status.next_execution');
    $request_time = $this->time->getRequestTime();
    $next_execution = !empty($next_execution) ? $next_execution : $request_time;

    $args = [
      '%time' => date('c', $this->state->get('endpoint_status.next_execution')),
      '%seconds' => $next_execution - $request_time,
    ];
    $form['status']['last'] = [
      '#type' => 'item',
      '#markup' => $this->t('endpoint_status_cron() will next execute the first time cron runs after %time (%seconds seconds from now)', $args),
    ];

    if ($this->currentUser->hasPermission('administer site configuration')) {
      $form['cron_run'] = [
        '#type' => 'details',
        '#title' => $this->t('Run cron manually'),
        '#open' => TRUE,
      ];
      $form['cron_run']['cron_reset'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Run endpoint_status's cron regardless of whether interval has expired."),
        '#default_value' => FALSE,
      ];
      $form['cron_run']['cron_trigger']['actions'] = ['#type' => 'actions'];
      $form['cron_run']['cron_trigger']['actions']['sumbit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Run cron now'),
        '#submit' => [[$this, 'cronRun']],
      ];
    }

    $form['cron_queue_setup'] = [
      '#type' => 'details',
      '#title' => $this->t('Cron queue setup (for hook_cron_queue_info(), etc.)'),
      '#open' => TRUE,
    ];

    $queue_1 = $this->queue->get('endpoint_status_queue');

    $args = [
      '%queue_1' => $queue_1->numberOfItems(),
    ];
    $form['cron_queue_setup']['current_cron_queue_status'] = [
      '#type' => 'item',
      '#markup' => $this->t('There are currently %queue_1 items in queue 1', $args),
    ];

    $items = \Drupal::entityTypeManager()->getStorage('endpoint_status')->loadMultiple();
    $itemOptions = array_reduce($items, function($carry, $item){
      $carry[$item->id()] = $item->label();
      return $carry;
    },[]);

    $form['cron_queue_setup']['items_to_add'] = [
      '#type' => 'select',
      '#title' => $this->t('The items to add to queue'),
      '#options' => $itemOptions,
      '#default_value' => NULL,
      '#multiple' => TRUE
    ];
    $form['cron_queue_setup']['queue'] = [
      '#type' => 'radios',
      '#title' => $this->t('Queue to add items to'),
      '#options' => [
        'endpoint_status_queue' => $this->t('Queue 1'),
      ],
      '#default_value' => 'endpoint_status_queue',
    ];
    $form['cron_queue_setup']['actions'] = ['#type' => 'actions'];
    $form['cron_queue_setup']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add jobs to queue'),
      '#submit' => [[$this, 'addItems']],
    ];

    $form['configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration of endpoint_status_cron()'),
      '#open' => TRUE,
    ];
    $form['configuration']['endpoint_status_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Cron interval'),
      '#description' => $this->t('Time after which endpoint_status_cron will respond to a processing request.'),
      '#default_value' => $config->get('interval'),
      '#options' => [
        60 => $this->t('1 minute'),
        300 => $this->t('5 minutes'),
        3600 => $this->t('1 hour'),
        86400 => $this->t('1 day'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Allow user to directly execute cron, optionally forcing it.
   */
  public function cronRun(array &$form, FormStateInterface &$form_state) {
    $cron_reset = $form_state->getValue('cron_reset');
    if (!empty($cron_reset)) {
      $this->state->set('endpoint_status.next_execution', 0);
    }

    // Use a state variable to signal that cron was run manually from this form.
    $this->state->set('endpoint_status_show_status_message', TRUE);
    if ($this->cron->run()) {
      $this->messenger()->addMessage($this->t('Cron ran successfully.'));
    }
    else {
      $this->messenger()->addError($this->t('Cron run failed.'));
    }
  }

  /**
   * Add the items to the queue when signaled by the form.
   */
  public function addItems(array &$form, FormStateInterface &$form_state) {
    $values = $form_state->getValues();
    $queue_name = $form['cron_queue_setup']['queue'][$values['queue']]['#title'];
    $items_to_add = $form_state->getValue('items_to_add');
    $endpointIds = array_keys($items_to_add);
    // Queues are defined by a QueueWorker Plugin which are selected by their
    // id attritbute.
    // @see \Drupal\endpoint_status\Plugin\QueueWorker\EndpointStatusWorkerOne
    $queue = $this->queue->get($values['queue']);

    $items = \Drupal::entityTypeManager()->getStorage('endpoint_status')->loadMultiple($endpointIds);
    foreach ($items as $item){
      $queue->createItem($item);
    }

    $args = [
      '%num' => count($items),
      '%queue' => $queue_name,
    ];
    $this->messenger()->addMessage($this->t('Added %num items to %queue', $args));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update the interval as stored in configuration. This will be read when
    // this modules hook_cron function fires and will be used to ensure that
    // action is taken only after the appropiate time has elapsed.
    $this->config('endpoint_status.settings')
      ->set('interval', $form_state->getValue('endpoint_status_interval'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['endpoint_status.settings'];
  }

}
