<?php

namespace Drupal\devel\Tests\Dpq;

use Drupal\Core\Database\Query\Select;
use Drupal\devel\Dpq\IndentedText;
use Drupal\devel\Dpq\SelectPrinter;
use Drupal\devel\Tests\Dpq\MockDatabaseConnection;
use Drupal\Tests\UnitTestCase;

class SelectPrinterTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Devel: SelectPrinter unit test',
      'description' => 'Tests that Drupal\devel\Dpq\SelectPrinter works as expected.',
      'group' => 'Devel',
    );
  }

  /**
   * @param Select $query
   * @param string $expected
   *
   * @dataProvider providerTestSelectPrinter
   */
  public function testSelectPrinter(Select $query, $expected) {
    $text = '';
    $indented_text = new IndentedText($text);
    SelectPrinter::printSelectQuery($indented_text, $query);
    $this->assertEquals($expected, $text);
  }

  /**
   * @return array
   */
  public function providerTestSelectPrinter() {
    $argument_combos = array();
    $connection = new MockDatabaseConnection();
    $q_nested = new Select('aaa', 'a', $connection);
    $q_nested->fields('a', array('x', 'y', 'b_id'));
    $q_nested->condition('z', 5);
    $q_nested->condition('y', 2.2, '>');
    $q_nested->groupBy('a.b_id');
    $q = new Select('bbb', 'b', $connection);
    $q->leftJoin($q_nested, 'nested', 'nested.b_id = b.id');
    $q->fields('nested');
    $q->fields('b', array('width', 'height'));
    $q->orderBy('b.width', 'ASC');
    $expected = <<<'EOT'

SELECT
  nested.*,
  b.width AS width,
  b.height AS height
FROM
  bbb b
  LEFT OUTER JOIN (
    SELECT
      a.x AS x,
      a.y AS y,
      a.b_id AS b_id
    FROM
      aaa a
    WHERE
      (z = 5) AND
      (y > 2.2)
    GROUP BY
      a.b_id
  ) nested ON
    nested.b_id = b.id
ORDER BY
  b.width ASC
EOT;

    $argument_combos[] = array($q, $expected);
    return $argument_combos;
  }

}