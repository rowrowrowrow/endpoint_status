<?php

namespace Drupal\endpoint_status\Plugin\EndpointStatusProcessor;

use Drupal;
use Drupal\Component\Utility\EmailValidator;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\endpoint_status\Constants\EndpointStatusConstants;
use Drupal\endpoint_status\EndpointStatusProcessor;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Throwable;

/**
 * Provides a default EndpointStatusProcessor.
 *
 * Because the plugin manager class for our plugins uses annotated class
 * discovery, our meatball endpoint status processor only needs to exist within
 * the Plugin\EndpointStatusProcessor namespace, and provide a
 * EndpointStatusProcessor annotation to be declared as a plugin. This is
 * defined in
 * \Drupal\endpoint_status\EndpointStatusProcessorPluginManager::__construct().
 *
 * The following is the plugin annotation. This is parsed by Doctrine to make
 * the plugin definition. Any values defined here will be available in the
 * plugin definition.
 *
 * This should be used for metadata that is specifically required to
 * instantiate
 * the plugin, or for example data that might be needed to display a list of
 * all
 * available plugins where the user selects one. This means many plugin
 * annotations can be reduced to a plugin ID, a label and perhaps a
 * description.
 *
 * @EndpointStatusProcessor(
 *   id = "default",
 *   description = @Translation("The default processor for endpoints."),
 *   label = @Translation("Default"),
 * )
 */
class DefaultEndpointStatusProcessor extends EndpointStatusProcessor implements ContainerFactoryPluginInterface {

  // Use Drupal\Core\StringTranslation\StringTranslationTrait to define
  // $this->t() for string translations in our plugin.
  use StringTranslationTrait;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidator
   */
  protected $emailValidator;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * EndpointStatusWorkerBase constructor.
   *
   * @param array $configuration
   *   The configuration of the instance.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Component\Utility\EmailValidator $email_validator
   *   The email validator.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service the instance should use.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, EmailValidator $email_validator, LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->emailValidator = $email_validator;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.mail'),
      $container->get('language_manager'),
      $container->get('email.validator'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processEndpointStatus($endpointStatus) {
    $uri = $endpointStatus->get('uri');
    $status = $endpointStatus->get('status');
    $message = $endpointStatus->get('message');
    $email_subscribers = $endpointStatus->get('email_subscribers');

    $client = Drupal::httpClient();
    try {
      /** @var \GuzzleHttp\Psr7\Response $response */
      $response = $client->get($uri);
      if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
        $endpointStatus->set('status', EndpointStatusConstants::UP);
      }
      else {
        $endpointStatus->set('status', EndpointStatusConstants::DOWN);
      }

      $contents = $response->getBody()->getContents();
      try {
        $asJSON = json_decode($contents);
        $endpointStatus->set('message', $this->t("Feed content is well formed."));
      } catch (Throwable $e) {
        $endpointStatus->set('message', $e->getTraceAsString());
        $endpointStatus->set('status', EndpointStatusConstants::MALFORMED);
      }

      $endpointStatus->save();
    } catch (RequestException $e) {
      $endpointStatus->set('status', EndpointStatusConstants::DOWN);
      $endpointStatus->set('message', $e->getTraceAsString());
      $endpointStatus->save();
    } catch (Throwable $e) {
      $endpointStatus->set('status', EndpointStatusConstants::UNPROCESSABLE);
      $endpointStatus->set('message', $e->getTraceAsString());
      $endpointStatus->save();
    }

    if ($status != $endpointStatus->get('status') || $message != $endpointStatus->get('message')) {
      $module = 'endpoint_status';
      $key = $endpointStatus->id();
      $language_code = $this->languageManager->getDefaultLanguage()->getId();
      $params = [
        'endpoint_status' => $endpointStatus,
      ];
      $from = Drupal::config('system.site')->get('mail');
      $send_now = TRUE;

      foreach ($email_subscribers as $to) {
        if ($this->emailValidator->isValid($to)) {
          $user = user_load_by_mail($to);
          $result =  NULL;

          if ($user) {
            $preferredLangcode = $user->getPreferredLangcode(TRUE);
            $result = $this->mailManager->mail($module, $key, $to, $preferredLangcode, $params, $from, $send_now);
          }
          else {
            $result = $this->mailManager->mail($module, $key, $to, $language_code, $params, $from, $send_now);
          }

          if ($result['result'] != TRUE) {
            $this->logger->get('endpoint_status')->error('Unable to send Endpoint Status Update Email to @to for endpoint status with id @id and label @label', [
              '@to' => $to,
              '@id' => $endpointStatus->id(),
              '@label' => $endpointStatus->label(),
            ]);
          }
        }
      }
    }

  }

}
