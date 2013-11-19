<?php

namespace Drupal\devel\Dpq;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\Select;

abstract class SelectPrinter extends Select {

  /**
   * Print a select query, with more linebreaks and indentation than usual.
   *
   * @param Select $q
   *   The query object we want to print.
   *
   * @return string
   *   Formatted text for the query.
   */
  public static function printSelectQuery(Select $q) {

    $text = '';

    // Compile the query, if it is not compiled yet.
    if (!$q->compiled()) {
      $q->compile($q->connection, $q);
    }

    // SELECT
    $text .= "\n" . 'SELECT';
    if ($q->distinct) {
      $text .= "\n" . ' DISTINCT';
    }

    // FIELDS and EXPRESSIONS
    $text .= Util::indent(self::printFields($q));

    // FROM - We presume all queries have a FROM, as any query that doesn't won't need the query builder anyway.
    $text .= "\n" . 'FROM';
    $text .= Util::indent(self::printFrom($q));

    // WHERE
    if (count($q->where)) {
      $text .= "\n" . 'WHERE';
      $text .= Util::indent(ConditionPrinter::printCondition($q->where));
    }

    // GROUP BY
    if ($q->group) {
      $text .= "\n" . 'GROUP BY';
      $text .= Util::indentList($q->group);
    }

    // HAVING
    if (count($q->having)) {
      // There is an implicit string cast on $q->having.
      $text .= "\n" . 'HAVING ' . $q->having;
    }

    // ORDER BY
    if ($q->order) {
      $text .= "\n" . 'ORDER BY';
      $fields = array();
      foreach ($q->order as $field => $direction) {
        $fields[] = $field . ' ' . $direction;
      }
      $text .= Util::indentList($fields);
    }

    // RANGE
    // There is no universal SQL standard for handling range or limit clauses.
    // Fortunately, all core-supported databases use the same range syntax.
    // Databases that need a different syntax can override this method and
    // do whatever alternate logic they need to.
    if (!empty($q->range)) {
      $text .= "\n" . 'LIMIT ' . (int) $q->range['length'] . ' OFFSET ' . (int) $q->range['start'];
    }

    // UNION is a little odd, as the select queries to combine are passed into
    // this query, but syntactically they all end up on the same level.
    if ($q->union) {
      foreach ($q->union as $union) {
        $text .= "\n" . $union['type'] . ' ' . (string) $union['query'];
      }
    }

    if ($q->forUpdate) {
      $text .= "\n" . 'FOR UPDATE';
    }

    return $text;
  }

  /**
   * Print the fields information of a query.
   *
   * @param Select $q
   *   The query object we want to print.
   *
   * @return string
   *   Formatted text representing the fields.
   */
  protected static function printFields(Select $q) {
    $fields = array();
    foreach ($q->tables as $alias => $table) {
      if (!empty($table['all_fields'])) {
        $fields[] = $q->connection->escapeTable($alias) . '.*';
      }
    }
    foreach ($q->fields as $field) {
      $str = $q->connection->escapeField($field['field']);
      if (isset($field['table'])) {
        $str = $q->connection->escapeTable($field['table']) . '.' . $str;
      }
      // Always use the AS keyword for field aliases, as some
      // databases require it (e.g., PostgreSQL).
      $fields[] = $str . ' AS ' . $q->connection->escapeAlias($field['alias']);
    }
    foreach ($q->expressions as $expression) {
      $fields[] = $expression['expression'] . ' AS ' . $q->connection->escapeAlias($expression['alias']);
    }
    return Util::printList($fields);
  }

  /**
   * Print the FROM information of a query.
   *
   * @param Select $q
   *   The query object we want to print.
   *
   * @return string
   *   Formatted text representing the FROM statement.
   *
   * @throws \Exception
   */
  protected static function printFrom(Select $q) {
    $text = '';

    foreach ($q->tables as $alias => $table) {
      $text .= "\n";
      if (isset($table['join type'])) {
        $text .= $table['join type'] . ' JOIN ';
      }

      // Find out whether the table is a subquery or a table name.
      if (is_string($table['table'])) {
        $text .= $q->connection->escapeTable($table['table']);
      }
      elseif ($table['table'] instanceof Select) {
        // The table is a subquery. Compile it, and integrate it into the parent
        // query.
        // Run preparation steps on this sub-query before converting to string.
        $subquery = $table['table'];
        $subquery->preExecute();
        $subquery->__toString();
        $text .= '(';
        $text .= Util::indent(self::printSelectQuery($subquery));
        $text .= "\n" . ')';
      }
      elseif ($table['table'] instanceof SelectInterface) {
        $subquery_class = get_class($table['table']);
        throw new \Exception("Table at \$q->tables[$alias]['table'] is of class $subquery_class, which is not supported by devel dpq().");
      }
      else {
        throw new \Exception("Table at \$q->tables[$alias]['table'] must be a string, or an instance of Drupal\\Core\\Database\\Query\\SelectInterface.");
      }

      // Don't use the AS keyword for table aliases, as some
      // databases don't support it (e.g., Oracle).
      $text .= ' ' . $q->connection->escapeTable($table['alias']);

      if (!empty($table['condition'])) {
        $text .= ' ON';
        $text .= Util::indentList($table['condition'], ' AND');
      }
    }

    return $text;
  }
}
