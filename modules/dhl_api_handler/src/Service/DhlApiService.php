<?php

namespace Drupal\dhl_api_handler\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class DhlApiService.
 *
 * @package Drupal\dhl_api_handler\Service
 */
class DhlApiService {
    protected $httpClient;
    protected $configFactory;

    public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
        $this->httpClient = $http_client;
        $this->configFactory = $config_factory;
    }

    public function listLocations($country, $city, $postal_code) {
        $config = $this->configFactory->get('dhl_api_handler.settings');
        $api_key = $config->get('dhl_api_key');
        $api_base_url = $config->get('dhl_api_base_url');
        $url = $api_base_url . '/v1/find-by-address';

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'DHL-API-Key' => $api_key,
                ],
                'query' => [
                    'countryCode' => $country,
                    'postalCode' => $postal_code,
                    'city' => $city,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return $data;
        } catch (\Exception $e) {
            \Drupal::logger('dhl_api_handler')->error($e->getMessage());
            return null;
        }
    }
}
