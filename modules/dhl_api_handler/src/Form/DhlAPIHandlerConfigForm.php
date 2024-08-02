<?php

namespace Drupal\dhl_api_handler\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DhlAPIHandlerConfigForm extends ConfigFormBase {
    protected function getEditableConfigNames() {
        return ['dhl_api_handler.settings'];
    }

    public function getFormId() {
        return 'dhl_api_handler_config_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('dhl_api_handler.settings');

        $form['dhl_api_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('DHL API Key'),
            '#default_value' => $config->get('dhl_api_key'),
            '#required' => true,
        ];

        $form['dhl_api_base_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('DHL API base URL'),
            '#default_value' => $config->get('dhl_api_base_url'),
            '#required' => true,
        ];

        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('dhl_api_handler.settings')
        ->set('dhl_api_key', $form_state->getValue('dhl_api_key'))
        ->set('dhl_api_base_url', $form_state->getValue('dhl_api_base_url'))
        ->save();

        parent::submitForm($form, $form_state);
    }
}
