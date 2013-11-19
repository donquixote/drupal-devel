<?php


namespace Drupal\devel\Tests\Dpq;

use Drupal\Core\Database\Connection;


class MockDatabaseConnection extends Connection {

  /**
   * Constructs a MockDatabaseConnection object.
   */
  public function __construct() {
    parent::__construct(new MockPDO(), array());
  }

  /**
   * {@inheritdoc}
   */
  public function queryRange($query, $from, $count, array $args = array(), array $options = array()) {
    throw new \Exception("MockDatabaseQuery cannot execute queries.");
  }

  /**
   * {@inheritdoc}
   */
  public function queryTemporary($query, array $args = array(), array $options = array()) {
    throw new \Exception("MockDatabaseQuery cannot execute queries.");
  }

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'devel_mock_db_driver';
  }

  /**
   * {@inheritdoc}
   */
  public function databaseType() {
    return 'devel_mock_db_type';
  }

  /**
   * {@inheritdoc}
   */
  public function createDatabase($database) {
    throw new \Exception("MockDatabaseQuery cannot create databases.");
  }

  /**
   * {@inheritdoc}
   */
  public function mapConditionOperator($operator) {
    // We don't want to override any of the defaults.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function nextId($existing_id = 0) {
    throw new \Exception("MockDatabaseQuery cannot execute queries.");
  }
} 