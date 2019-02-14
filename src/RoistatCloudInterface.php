<?php

namespace Drupal\roistat;

/**
 * Interface RoistatCloudInterface.
 */
interface RoistatCloudInterface {

  /**
   * @param array $data
   *
   * @return mixed
   */
  public function leadsAdd(array $data);
}
