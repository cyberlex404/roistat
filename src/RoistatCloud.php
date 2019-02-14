<?php

namespace Drupal\roistat;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class RoistatCloud.
 */
class RoistatCloud implements RoistatCloudInterface {


  const API_LEADS_ADD = 'https://cloud.roistat.com/api/proxy/1.0/leads/add';
  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Integration key
   *
   * @var string
   */
  protected $key;
  /**
   * Constructs a new RoistatCloud object.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * @param array $data
   *
   * @return bool|mixed
   * @throws \Exception
   */
  public function leadsAdd(array $data) {
    // set integration key
    if (!isset($data['key'])) {
      $data['key'] = $this->getKey();
    }

    $isContactField = isset($data['phone']) || isset($data['email']);

    if (!$isContactField) {
      throw new \Exception('The value of one of the required fields is not specified');
    }

    if (!isset($data['key'])) {
      throw new \Exception('No integration key specified');
    }

    try {
      $response = $this->httpClient->request('POST', self::API_LEADS_ADD, [
        'query' => $data,
      ]);

    }catch (GuzzleException $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
    }

    $contents = $response->getBody()->getContents();
    $result = Json::decode($contents);
    if ($result['status'] == 'success') {
      return $result;
    }else{
      $message = is_string($result['data']) ? $result['data'] : 'Unknown error';
      \Drupal::logger('roistat')->error($message);
      //\Drupal::messenger()->addError($message);
      return FALSE;
    }
  }

  /**
   * Return integration key
   *
   * @return string|null
   */
  protected function getKey() {
    if (!$this->key) {
      $this->key = $this->configFactory->get('roistat.settings')->get('integration_key');
    }
    return $this->key;
  }

}
