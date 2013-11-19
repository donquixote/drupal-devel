<?php


namespace Drupal\devel\Dpq;

use Drupal\Core\Database\Query\Condition;

abstract class ConditionPrinter extends Condition {

  /**
   * Print the WHERE information of a query.
   *
   * @param IndentedText $out
   *   An object that we can write to.
   * @param Condition $cond
   *   A database condition object we want to print.
   */
  public static function printCondition($out, $cond) {
    $printed = trim($cond);
    if (!empty($cond->arguments)) {
      $printed = strtr($printed, $cond->arguments);
    }
    $out->printList($printed, ' AND');
  }
}