<?php


namespace Drupal\devel\Dpq;

/**
 * Contains helper methods for string formatting.
 */
class Util {

  /**
   * @param $text
   * @param string $indent
   * @return mixed
   */
  public static function indent($text, $indent = '  ') {
    return str_replace("\n", "\n" . $indent, $text);
  }

  /**
   * Interpret a string as a list with separators,
   * and print it with added linebreaks.
   *
   * @param string|array $list
   *   String with separators, e.g "x = 5 AND y = 7 AND z > 2".
   * @param string $separator
   *   Separator string, e.g. "AND" or ",".
   *
   * @return string
   */
  public static function printList($list, $separator = ',') {
    if (is_string($list)) {
      $list = explode($separator . ' ', $list);
    }
    return "\n" . implode("$separator\n", $list);
  }

  /**
   * @param string|array $list
   * @param string $separator
   * @param string $indent
   * @return mixed
   */
  public static function indentList($list, $separator = ',', $indent = '  ') {
    $text = self::printList($list, $separator);
    return self::indent($text, $indent);
  }

}