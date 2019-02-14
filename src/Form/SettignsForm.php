<?php

namespace Drupal\roistat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettignsForm.
 */
class SettignsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'roistat.settigns',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settigns_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('roistat.settigns');

    $form['integration_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Integration key'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('integration_key'),
      '#required' => TRUE,
    ];
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

    $this->config('roistat.settigns')
      ->set('integration_key', $form_state->getValue('integration_key'))
      ->save();
  }

}
