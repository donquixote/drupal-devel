<?php


namespace Drupal\devel\Dpq;

use Drupal\Core\Database\Query\Condition;

abstract class ConditionPrinter extends Condition {

  /**
   * Print the WHERE information of a query.
   *
   * @param Condition $cond
   *   A database condition object we want to print.
   *
   * @return string
   */
  public static function printCondition(Condition $cond) {
    $printed = trim($cond);
    if (!empty($cond->arguments)) {
      $printed = strtr($printed, $cond->arguments);
    }
    return Util::printList($printed, ' AND');
  }
}