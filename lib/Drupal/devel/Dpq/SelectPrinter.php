<?php

namespace Drupal\devel\Dpq;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\Select;

/**
 * Uses inheritance to access protected properties of Select queries.
 */
abstract class SelectPrinter extends Select {

  /**
   * Print a select query, with more linebreaks and indentation than usual.
   *
   * @param Select $query
   *   The query object we want to print.
   *
   * @return string
   *   Formatted text for the query.
   */
  public static function printSelectQuery(Select $query) {

    $text = '';

    // Prepare the query.
    self::prepareQuery($query);

    // SELECT
    $text .= "\n" . 'SELECT';
    if ($query->distinct) {
      $text .= "\n" . ' DISTINCT';
    }

    // FIELDS and EXPRESSIONS
    $text .= Util::indent(self::printFields($query));

    // FROM - We presume all queries have a FROM, as any query that doesn't won't need the query builder anyway.
    $text .= "\n" . 'FROM';
    $text .= Util::indent(self::printFrom($query));

    // WHERE
    if (count($query->where)) {
      $text .= "\n" . 'WHERE';
      $text .= Util::indent(ConditionPrinter::printCondition($query->where));
    }

    // GROUP BY
    if ($query->group) {
      $text .= "\n" . 'GROUP BY';
      $text .= Util::indentList($query->group);
    }

    // HAVING
    if (count($query->having)) {
      // There is an implicit string cast on $q->having.
      $text .= "\n" . 'HAVING ' . $query->having;
    }

    // ORDER BY
    if ($query->order) {
      $text .= "\n" . 'ORDER BY';
      $fields = array();
      foreach ($query->order as $field => $direction) {
        $fields[] = $field . ' ' . $direction;
      }
      $text .= Util::indentList($fields);
    }

    // RANGE
    // There is no universal SQL standard for handling range or limit clauses.
    // Fortunately, all core-supported databases use the same range syntax.
    // Databases that need a different syntax can override this method and
    // do whatever alternate logic they need to.
    if (!empty($query->range)) {
      $text .= "\n" . 'LIMIT ' . (int) $query->range['length'] . ' OFFSET ' . (int) $query->range['start'];
    }

    // UNION is a little odd, as the select queries to combine are passed into
    // this query, but syntactically they all end up on the same level.
    if ($query->union) {
      foreach ($query->union as $union) {
        $text .= "\n" . $union['type'] . ' ' . (string) $union['query'];
      }
    }

    if ($query->forUpdate) {
      $text .= "\n" . 'FOR UPDATE';
    }

    return $text;
  }

  /**
   * Print the fields information of a query.
   *
   * @param Select $query
   *   The query object we want to print.
   *
   * @return string
   *   Formatted text representing the fields.
   */
  protected static function printFields(Select $query) {
    $fields = array();
    foreach ($query->tables as $alias => $table) {
      if (!empty($table['all_fields'])) {
        $fields[] = $query->connection->escapeTable($alias) . '.*';
      }
    }
    foreach ($query->fields as $field) {
      $str = $query->connection->escapeField($field['field']);
      if (isset($field['table'])) {
        $str = $query->connection->escapeTable($field['table']) . '.' . $str;
      }
      // Always use the AS keyword for field aliases, as some
      // databases require it (e.g., PostgreSQL).
      $fields[] = $str . ' AS ' . $query->connection->escapeAlias($field['alias']);
    }
    foreach ($query->expressions as $expression) {
      $fields[] = $expression['expression'] . ' AS ' . $query->connection->escapeAlias($expression['alias']);
    }
    return Util::printList($fields);
  }

  /**
   * Print the FROM information of a query.
   *
   * @param Select $query
   *   The query object we want to print.
   *
   * @return string
   *   Formatted text representing the FROM statement.
   *
   * @throws DpqException
   */
  protected static function printFrom(Select $query) {
    $text = '';

    foreach ($query->tables as $alias => $table) {
      $text .= "\n";
      if (isset($table['join type'])) {
        $text .= $table['join type'] . ' JOIN ';
      }

      // Find out whether the table is a subquery or a table name.
      if (is_string($table['table'])) {
        $text .= $query->connection->escapeTable($table['table']);
      }
      elseif ($table['table'] instanceof Select) {
        // The table is a subquery.
        $subquery = $table['table'];
        // Prepare the subquery.
        self::prepareQuery($subquery);
        $text .= '(';
        $text .= Util::indent(self::printSelectQuery($subquery));
        $text .= "\n" . ')';
      }
      elseif ($table['table'] instanceof SelectInterface) {
        $subquery_class = get_class($table['table']);
        throw new DpqException("Table at \$q->tables[$alias]['table'] is of class $subquery_class, which is not supported by devel dpq().");
      }
      else {
        throw new DpqException("Table at \$q->tables[$alias]['table'] must be a string, or an instance of Drupal\\Core\\Database\\Query\\SelectInterface.");
      }

      // Don't use the AS keyword for table aliases, as some
      // databases don't support it (e.g., Oracle).
      $text .= ' ' . $query->connection->escapeTable($table['alias']);

      if (!empty($table['condition'])) {
        $text .= ' ON';
        $text .= Util::indentList($table['condition'], ' AND');
      }
    }

    return $text;
  }

  /**
   * @param Select $query
   */
  public static function prepareQuery(Select $query) {

    // Prepare the query, if it is not prepared yet.
    if (!$query->isPrepared()) {
      $query->preExecute();
    }

    // Compile the query, if it is not compiled yet.
    if (!$query->compiled()) {
      $query->compile($query->connection, $query);
    }
  }

}