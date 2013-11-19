<?php


namespace Drupal\devel\Dpq;

use Drupal\Core\Database\Query\Condition;

abstract class ConditionPrinter extends Condition {

  /**
   * Print the WHERE information of a query.
   *
   * @param Condition $condition
   *   A database condition object we want to print.
   *
   * @return string
   */
  public static function printCondition(Condition $condition) {
    $printed = trim($condition);
    if (!empty($condition->arguments)) {
      $printed = strtr($printed, $condition->arguments);
    }
    return Util::printList($printed, ' AND');
  }
}