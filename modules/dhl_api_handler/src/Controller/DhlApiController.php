<?php

namespace Drupal\dhl_api_handler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for DHL API handler.
 */
class DhlApiController extends ControllerBase {

  protected $configFactory;

  public function __construct(ConfigFactoryInterface $config_factory) {
      $this->configFactory = $config_factory;
  }

  public static function create(ContainerInterface $container) {
      return new static(
          $container->get('config.factory')
      );
  }

  public function results() {
      $yaml_output = \Drupal::state()->get('dhl_api_handler.results', '');

      return new Response(
          $yaml_output,
          Response::HTTP_OK,
          ['Content-Type' => 'text/plain']
      );
  }
}