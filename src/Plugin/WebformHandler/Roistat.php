<?php

namespace Drupal\roistat\Plugin\WebformHandler;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\roistat\RoistatCloudInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformThemeManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Roistat handler.
 *
 * @WebformHandler(
 *   id = "roistat",
 *   label = @Translation("Roistat"),
 *   category = @Translation("Roistat"),
 *   description = @Translation("Sends web form submission to roistat."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class Roistat extends WebformHandlerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A mail manager for sending email.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform theme manager.
   *
   * @var \Drupal\webform\WebformThemeManagerInterface
   */
  protected $themeManager;

  /**
   * A webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * @var \Drupal\roistat\RoistatCloud
   */
  protected $roistatCloud;
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              LoggerChannelFactoryInterface $logger_factory,
                              ConfigFactoryInterface $config_factory,
                              EntityTypeManagerInterface $entity_type_manager,
                              WebformSubmissionConditionsValidatorInterface $conditions_validator,
                              AccountInterface $current_user,
                              ModuleHandlerInterface $module_handler,
                              LanguageManagerInterface $language_manager,
                              WebformThemeManagerInterface $theme_manager,
                              WebformTokenManagerInterface $token_manager,
                              WebformElementManagerInterface $element_manager,
                              RoistatCloudInterface $roistatCloud) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->themeManager = $theme_manager;
    $this->tokenManager = $token_manager;
    $this->elementManager = $element_manager;
    $this->roistatCloud = $roistatCloud;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('webform.theme_manager'),
      $container->get('webform.token_manager'),
      $container->get('plugin.manager.webform.element'),
      $container->get('roistat.cloud')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'roistat_integration_key' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form_state);

    $form['#attributes']['novalidate'] = 'novalidate';
    $form['roistat_integration_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Integration key'),
      '#default_value' => $this->configuration['roistat_integration_key'],
    ];

    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Fields'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['fields']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->configuration['fields']['title'],
    ];
    $form['fields']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $this->configuration['fields']['name'],
    ];
    $form['fields']['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#default_value' => $this->configuration['fields']['phone'],
    ];
    $form['fields']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail'),
      '#default_value' => $this->configuration['fields']['email'],
    ];

    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValues();

    $this->configuration['roistat_integration_key'] = $values['roistat_integration_key'];
    $this->configuration['fields'] = $values['fields'];

  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();

    $data = $this->getData($webform_submission);

    if (!empty($this->configuration['roistat_integration_key'])) {
      $data['key'] = $this->configuration['roistat_integration_key'];
    }

    try{
      $result = $this->roistatCloud->leadsAdd($data);
    }catch (\Exception $e) {
      $this->getLogger('roistat')->error($e->getMessage());
      \Drupal::messenger()->addError($e->getMessage());
    }

  }

  /**
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *
   * @return array
   */
  protected function getData(WebformSubmissionInterface $webform_submission) {
    $data = [];
    $token_data = [];
    $token_options = [
      'clear' => TRUE,
    ];
    $fields = $this->configuration['fields'];
    foreach ($fields as $name => $value) {
      if (!empty($value)) {
        $data[$name] = $this->tokenManager->replace($value, $webform_submission, $token_data, $token_options);
      }
    }
    return $data;
  }


  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $settings = $this->getConfiguration()['settings'];

    return [
        '#settings' => $settings,
      ] + parent::getSummary();
  }


}
