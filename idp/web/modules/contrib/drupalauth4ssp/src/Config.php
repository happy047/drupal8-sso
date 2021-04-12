<?php

namespace Drupal\drupalauth4ssp;

use Drupal\Core\Config\ConfigFactoryInterface;
use SimpleSAML\Configuration as SimpleSAMLConfiguration;
use SimpleSAML\Utils\Config as SimpleSAMLConfig;

/**
 * Config service.
 */
class Config {

  /**
   * Cookie name.
   *
   * @var string
   */
  private $cookieName;

  /**
   * List of allowed URLs.
   *
   * @var string
   */
  private $returnToList;

  /**
   * SimpleSAMLphp secret salt.
   *
   * @var string
   */
  private $secretSalt;

  /**
   * SimpleSAMLphp base path.
   *
   * @var string
   */
  private $basePath;

  /**
   * Constructs a config object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $config = $config_factory->get('drupalauth4ssp.settings');
    $this->cookieName = $config->get('cookie_name');;
    $this->returnToList = $config->get('returnto_list');;

    // Get the secretsalt.
    $this->secretSalt = SimpleSAMLConfig::getSecretSalt();

    // Get the baseurlpath.
    $this->basePath = SimpleSAMLConfiguration::getInstance()->getBasePath();
  }

  /**
   * Returns cookie name.
   *
   * @return string
   *   Cookie name.
   */
  public function getCookieName() {
    return $this->cookieName;
  }

  /**
   * Returns allowed "return to" list.
   *
   * @return string
   *   List of allowed return to URLs.
   */
  public function getReturnToList() {
    return $this->returnToList;
  }

  /**
   * Returns secret salt.
   *
   * @return string
   *   Secret salt.
   */
  public function getSecretSalt() {
    return $this->secretSalt;
  }

  /**
   * Returns base path.
   *
   * @return string
   *   Base path.
   */
  public function getBasePath() {
    return $this->basePath;
  }

}
