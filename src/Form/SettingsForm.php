<?php

namespace Drupal\roistat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'roistat.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('roistat.settings');

    $form['integration_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Integration key'),
      '#maxlength' => 250,
      '#size' => 64,
      '#default_value' => $config->get('integration_key'),
      '#required' => TRUE,
    ];

    $form['counter_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Counter code'),
      '#default_value' => $config->get('counter_code'),
      '#required' => TRUE,
    ];

    $form['path'] = array(
      '#type' => 'details',
      '#title' => $this->t('Page specific tracking settings'),
      '#collapsed' => TRUE,
    );

    $visibility = $config->get('visibility.path.visibility');
    $options = array(
      $this->t('Add to every page except the listed pages.'),
      $this->t('Add to the listed pages only.')
    );
    $form['path']['visibility'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Add code to specific pages'),
      '#options' => $options,
      '#default_value' => $visibility,
    );

    $pages = $config->get('visibility.path.pages');

    $description = $this->t(
      "Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
      array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>')
    );
    $form['path']['pages'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#default_value' => $pages,
      '#description' => $description,
      '#wysiwyg' => FALSE,
      '#rows' => 10,
    );

    // Render the role overview.
    $form['role'] = array(
      '#type' => 'details',
      '#title' => $this->t('Role specific tracking settings'),
      '#collapsed' => TRUE,
    );

    $form['role']['visibility_roles'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Add tracking for specific roles'),
      '#options' => array(
        $this->t('Add to the selected roles only'),
        $this->t('Add to every role except the selected ones'),
      ),
      '#default_value' => $config->get('visibility.role.visibility'),
    );

    $role_options = array_map('\Drupal\Component\Utility\Html::escape', user_role_names());
    $form['role']['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => $config->get('visibility.role.roles'),
      '#options' => $role_options,
      '#description' => $this->t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $counter_code = $form_state->getValue('counter_code');
    $visibility = $form_state->getValue('visibility');
    $pages = $form_state->getValue('pages');
    $visibility_roles = $form_state->getValue('visibility_roles');
    $roles = $form_state->getValue('roles');

    $this->config('roistat.settings')
      ->set('integration_key', $form_state->getValue('integration_key'))
      ->set('counter_code', $counter_code)
      ->set('visibility.path.visibility', $visibility)
      ->set('visibility.path.pages', $pages)
      ->set('visibility.role.visibility', $visibility_roles)
      ->set('visibility.role.roles', $roles)
      ->save();
  }

}
