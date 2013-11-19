<?php


namespace Drupal\devel\Tests\Dpq;


class MockPDO extends \PDO {

  protected $attributes = array();

  public function __construct() {
    // Do nothing. Ignore the parent constructor.
    // See http://stackoverflow.com/questions/3138946/mocking-the-pdo-object-using-phpunit
  }

  /**
   * Overrides PDO::setAttribute()
   *
   * @param int $attribute
   * @param mixed $value
   * @return bool
   */
  public function setAttribute($attribute, $value) {
    $this->attributes[$attribute] = $value;
    return TRUE;
  }

  /**
   * Overrides PDO::getAttribute()
   *
   * @param int $attribute
   * @return mixed|null
   */
  public function getAttribute($attribute) {
    return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : NULL;
  }

}