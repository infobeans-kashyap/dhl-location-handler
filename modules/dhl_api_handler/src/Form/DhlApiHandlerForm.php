<?php

namespace Drupal\dhl_api_handler\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\dhl_api_handler\Service\DhlApiService;

/**
 * Provides a form for searching DHL locations.
 */
class DhlApiHandlerForm extends FormBase {
  protected $httpClient;
    protected $state;
    protected $messages;
    protected $configFactory;
    protected $dhlApiService;

    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $http_client, StateInterface $state, MessengerInterface $messages, ConfigFactoryInterface $config_factory, DhlApiService $dhlApiService) {
        $this->httpClient = $http_client;
        $this->state = $state;
        $this->messages = $messages;
        $this->configFactory = $config_factory;
        $this->dhlApiService = $dhlApiService;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('http_client'),
            $container->get('state'),
            $container->get('messenger'),
            $container->get('config.factory'),
            $container->get('dhl_api_handler.dhl_api_service')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'dhl_api_handler_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['country'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Country'),
            '#required' => true,
        ];

        $form['city'] = [
            '#type' => 'textfield',
            '#title' => $this->t('City'),
            '#required' => true,
        ];

        $form['postal_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Postal Code'),
            '#required' => true,
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Find Locations'),
        ];
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        $cou = $form_state->getValue('country');
        $city = $form_state->getValue('city');
        $postal = $form_state->getValue('postal_code');
    
        if (!preg_match('/^[A-Z]{2}$/', $cou)) {
          $form_state->setErrorByName('country', $this->t('The country code must be exactly 2 letters (e.g., DE).'));
        }
    
        if (empty($city)) {
          $form_state->setErrorByName('city', $this->t("City can't be empty."));
        }
    
        if (!ctype_digit($postal)) {
          $form_state->setErrorByName('postal_code', $this->t('The postal code can have only numbers.'));
        }
      }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $cou = $form_state->getValue('country');
        $city = $form_state->getValue('city');
        $postal = $form_state->getValue('postal_code');

        try {
            $response = $this->dhlApiService->listLocations($cou, $city, $postal);
            if ($response) {
                $data = $response;
                if (isset($data['locations'])) {
                    $locations = [];
                    foreach ($data['locations'] as $location) {
                        if ($this->isAValidLocation($location)) {
                            $filtered_location = [
                                'locationName' => $location['name'],
                                'address' => $location['place']['address'],
                                'openingHours' => []
                            ];
        
                            foreach ($location['openingHours'] as $opening_hours) {
                                $day_of_week_array = explode("/", $opening_hours['dayOfWeek']);
                                $day_of_week = end($day_of_week_array);
                                $filtered_location['openingHours'][$day_of_week] = $opening_hours['opens'] . " - "
                                    . $opening_hours['closes'];
                            }
        
                            $locations[] = $filtered_location ?? [];
                        }
                    }
                    $output = Yaml::dump($locations);
                    $this->state->set('dhl_api_handler.results', $output);
                    $this->messages->addStatus('State set with data: ' . $output);
                    $form_state->setRedirect('dhl_api_handler.results');
                }
                else {
                    $this->messages->addError($this->t('No locations found!!!.'));
                }
            }
            else {
                $this->messages->addError($this->t('Invalid response receieved.'));
            }
        }
        catch (\Exception $e) {
            \Drupal::logger('dhl_api_handler')->error($e->getMessage());
            $this->messages->addError($this->t('An error occurred while retrieving locations!!!.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    private function isAValidLocation(array $location) {
        $works_on_weekends = array_reduce($location['openingHours'], function ($carry, $opening_hours) {
            $day_of_week_array = explode("/", $opening_hours['dayOfWeek']);
            $day_of_week = end($day_of_week_array);
            return $carry || in_array($day_of_week, ['Saturday', 'Sunday']);
        }, false);

        $address_parts = explode(' ', $location['place']['address']['streetAddress']);
        $street_number = end($address_parts);
        $is_odd_number = (int)$street_number % 2 !== 0;

        return $works_on_weekends && !$is_odd_number;
    }
}
