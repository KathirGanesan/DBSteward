<?php

/**
 * Output directory and file prefix specificity regression tests
 *
 * @package DBSteward
 * @license http://www.opensource.org/licenses/bsd-license.php Simplified BSD License
 * @author Nicholas J Kiraly <kiraly.nicholas@gmail.com>
 */

require_once __DIR__ . '/../dbstewardUnitTestBase.php';

class OutputFileSpecificityTest extends dbstewardUnitTestBase {

  public function setUp() {
    parent::setUp();
  }

  protected function setup_definition_xml(&$base_xml, &$strict_overlay_xml, &$new_table_xml) {
    $base_xml = <<<XML
<dbsteward>
  <database>
    <role>
      <application>app_application</application>
      <owner>postgres</owner>
      <replication>app_slony</replication>
      <readonly>app_readonly</readonly>
    </role>
  </database>
  <schema name="app" owner="ROLE_OWNER">
    <table name="action" owner="ROLE_OWNER" primaryKey="action" slonyId="100">
      <column name="action" type="character varying(16)" null="false" />
      <column name="description" type="character varying(200)"/>
      <rows columns="action, description">
        <row>
          <col>ACTION1</col>
          <col>Action 1</col>
        </row>
        <row>
          <col>ACTION2</col>
          <col>Action 2 Description</col>
        </row>
        <row>
          <col>ACTION3</col>
          <col/>
        </row>
        <row>
          <col>ACTION4</col>
          <col/>
        </row>
        <row>
          <col>ACTION99</col>
          <col>Action 99 Reserved</col>
        </row>
      </rows>
    </table>
  </schema>
</dbsteward>
XML;
    $strict_overlay_xml = <<<XML
<dbsteward>
  <schema name="app" owner="ROLE_OWNER">
    <table name="action">
      <column name="description" type="character varying(200)" null="false" />
      <rows columns="action, description">
        <row>
          <col>ACTION1</col>
          <col>Action 1 Alternate Description</col>
        </row>
        <row>
          <col>ACTION3</col>
          <col>Action 3 Custom Action</col>
        </row>
        <row>
          <col>ACTION4</col>
          <col>Action 4 Custom Action</col>
        </row>
        <row>
          <col>ACTION99</col>
          <col>Action 99 Override</col>
        </row>
      </rows>
    </table>
  </schema>
</dbsteward>
XML;
    $new_table_xml = <<<XML
<dbsteward>
  <schema name="app" owner="ROLE_OWNER">
    <table name="resolution" owner="ROLE_OWNER" primaryKey="resolution" slonyId="105">
      <column name="resolution" type="character varying(16)" null="false" />
      <column name="points" type="int" />
      <rows columns="resolution, points">
        <row>
          <col>RESOLUTION1</col>
          <col>5</col>
        </row>
        <row>
          <col>RESOLUTION2</col>
          <col>2</col>
        </row>
        <row>
          <col>RESOLUTION3</col>
          <col/>
        </row>
        <row>
          <col>RESOLUTION4</col>
          <col/>
        </row>
        <row>
          <col>RESOLUTION99</col>
          <col>99</col>
        </row>
      </rows>
    </table>
  </schema>
</dbsteward>
XML;
  }

  protected function setup_pgsql8($output_file_dir, $output_file_prefix) {
    $base_xml = '';
    $strict_overlay_xml = '';
    $new_table_xml = '';
    $this->setup_definition_xml($base_xml, $strict_overlay_xml, $new_table_xml);

    $this->xml_file_a = dirname(__FILE__) . '/../testdata/pgsql8_unit_test_xml_a.xml';
    $this->xml_file_b = dirname(__FILE__) . '/../testdata/pgsql8_unit_test_xml_b.xml';
    $this->xml_file_c = dirname(__FILE__) . '/../testdata/pgsql8_unit_test_xml_c.xml';

    $this->set_xml_content_a($base_xml);
    $this->set_xml_content_b($strict_overlay_xml);
    $this->set_xml_content_c($new_table_xml);

    dbsteward::$quote_column_names = TRUE;
    dbsteward::$single_stage_upgrade = TRUE;
    dbsteward::$generate_slonik = FALSE;

    $this->setup_output_options('pgsql8', $output_file_dir, $output_file_prefix);
  }

  protected function setup_mysql5($output_file_dir, $output_file_prefix) {
    $base_xml = '';
    $strict_overlay_xml = '';
    $new_table_xml = '';
    $this->setup_definition_xml($base_xml, $strict_overlay_xml, $new_table_xml);

    $this->xml_file_a = dirname(__FILE__) . '/../testdata/mysql5_unit_test_xml_a.xml';
    $this->xml_file_b = dirname(__FILE__) . '/../testdata/mysql5_unit_test_xml_b.xml';
    $this->xml_file_c = dirname(__FILE__) . '/../testdata/mysql5_unit_test_xml_c.xml';

    $this->set_xml_content_a($base_xml);
    $this->set_xml_content_b($strict_overlay_xml);
    $this->set_xml_content_c($new_table_xml);

    dbsteward::$quote_column_names = TRUE;
    dbsteward::$single_stage_upgrade = TRUE;

    $this->setup_output_options('mysql5', $output_file_dir, $output_file_prefix);
  }

  public function output_variance_provider() {
    $output_dir1 = dirname(__FILE__) . '/../testdata_outputdir_dir_' . date("His");
    if (!is_dir($output_dir1)) {
      mkdir($output_dir1);
    }
    $output_dir2 = dirname(__FILE__) . '/../testdata_outputdir_fileprefix_' . date("His");
    if (!is_dir($output_dir2)) {
      mkdir($output_dir2);
    }

    return array(
      array(FALSE, FALSE),
      array($output_dir1, FALSE),
      array($output_dir2, 'gloopy_glop'),
      array(FALSE, 'solo_nobo'),
    );
  }

  protected function setup_output_options($setup_file_prefix, $output_file_dir, $output_file_prefix) {
    dbsteward::$file_output_directory = FALSE;
    dbsteward::$file_output_prefix = FALSE;

    if ($output_file_dir === FALSE) {
      $this->output_prefix = dirname(__FILE__) . '/../testdata';
    } else {
      $this->output_prefix = $output_file_dir;
      // match dbsteward specification
      dbsteward::$file_output_directory = $output_file_dir;
    }

    if ($output_file_prefix === FALSE) {
      $this->output_prefix .= '/' . $setup_file_prefix . '_test_column_nulls';
    } else {
      $this->output_prefix .= '/' . $setup_file_prefix . '_' . $output_file_prefix;
      // match dbsteward specification
      dbsteward::$file_output_prefix = $setup_file_prefix . '_' . $output_file_prefix;
    }
  }

  /**
   * @group pgsql8
   * @dataProvider output_variance_provider
   */
  public function testUpgradeIdenticalDDLPgsql8($output_file_dir, $output_file_prefix) {
    $this->apply_options_pgsql8();
    $this->setup_pgsql8($output_file_dir, $output_file_prefix);

    $base_db_doc = xml_parser::xml_composite(array($this->xml_file_a));
    $upgrade_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_a));
    pgsql8::build_upgrade('', 'column_nulls_identical_test_pgsql8_old', $base_db_doc, array(), $this->output_prefix, 'column_nulls_identical_test_pgsql8_new', $upgrade_db_doc, array());

    $text = file_get_contents($this->output_prefix . '_upgrade_single_stage.sql');
    $this->assertNotRegExp('/ALTER\s+/', $text, 'Diff SQL output contains ALTER statements');
    $this->assertNotRegExp('/UPDATE\s+/', $text, 'Diff SQL output contains UPDATE statements');
    $this->assertNotRegExp('/INSERT\s+/', $text, 'Diff SQL output contains INSERT statements');
    $this->assertNotRegExp('/DELETE\s+/', $text, 'Diff SQL output contains DELETE statements');


    // and do identical comparison with strict definitions on top
    $base_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_b));
    $upgrade_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_b));
    pgsql8::build_upgrade('', 'column_nulls_identical_strict_pgsql8_old', $base_db_doc, array(), $this->output_prefix, 'column_nulls_identical_strict_pgsql8_new', $upgrade_db_doc, array());

    $text = file_get_contents($this->output_prefix . '_upgrade_single_stage.sql');
    $this->assertNotRegExp('/ALTER\s+/', $text, 'Diff SQL output contains ALTER statements');
    $this->assertNotRegExp('/UPDATE\s+/', $text, 'Diff SQL output contains UPDATE statements');
    $this->assertNotRegExp('/INSERT\s+/', $text, 'Diff SQL output contains INSERT statements');
    $this->assertNotRegExp('/DELETE\s+/', $text, 'Diff SQL output contains DELETE statements');
  }

  /**
   * @group mysql5
   * @dataProvider output_variance_provider
   */
  public function testUpgradeIdenticalDDLMysql5($output_file_dir, $output_file_prefix) {
    $this->apply_options_mysql5();
    $this->setup_mysql5($output_file_dir, $output_file_prefix);

    $base_db_doc = xml_parser::xml_composite(array($this->xml_file_a));
    $upgrade_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_a));
    mysql5::build_upgrade('', 'column_nulls_identical_test_mysql5_old', $base_db_doc, array(), $this->output_prefix, 'column_nulls_identical_test_mysql5_new', $upgrade_db_doc, array());

    $text = file_get_contents($this->output_prefix . '_upgrade_single_stage.sql');

    $this->assertNotRegExp('/ALTER\s+/', $text, 'Diff SQL output contains ALTER statements');
    $this->assertNotRegExp('/UPDATE\s+/', $text, 'Diff SQL output contains UPDATE statements');
    $this->assertNotRegExp('/INSERT\s+/', $text, 'Diff SQL output contains INSERT statements');
    $this->assertNotRegExp('/DELETE\s+/', $text, 'Diff SQL output contains DELETE statements');


    // and do identical comparison with strict definitions on top
    $base_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_b));
    $upgrade_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_b));
    mysql5::build_upgrade('', 'column_nulls_identical_strict_mysql5_old', $base_db_doc, array(), $this->output_prefix, 'column_nulls_identical_strict_mysql5_new', $upgrade_db_doc, array());

    $text = file_get_contents($this->output_prefix . '_upgrade_single_stage.sql');

    $this->assertNotRegExp('/ALTER\s+/', $text, 'Diff SQL output contains ALTER statements');
    $this->assertNotRegExp('/UPDATE\s+/', $text, 'Diff SQL output contains UPDATE statements');
    $this->assertNotRegExp('/INSERT\s+/', $text, 'Diff SQL output contains INSERT statements');
    $this->assertNotRegExp('/DELETE\s+/', $text, 'Diff SQL output contains DELETE statements');
  }

  /**
   * @group pgsql8
   * @dataProvider output_variance_provider
   */
  public function testFullBuildPgsql8($output_file_dir, $output_file_prefix) {
    $this->apply_options_pgsql8();
    $this->setup_pgsql8($output_file_dir, $output_file_prefix);

    // build base full, check contents
    $base_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_a));
    pgsql8::build($this->output_prefix, $base_db_doc);
    $text = file_get_contents($this->output_prefix . '_build.sql');
    // make sure SET NOT NULL is specified for action column
    $this->assertContains('ALTER COLUMN "action" SET NOT NULL', $text);
    // make sure SET NOT NULL is NOT specified for description column
    $this->assertNotContains('ALTER COLUMN "description" SET NOT NULL', $text);

    // build base + strict, check contents
    $strict_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_b));
    pgsql8::build($this->output_prefix, $strict_db_doc);
    $text = file_get_contents($this->output_prefix . '_build.sql');
    // make sure SET NOT NULL is specified for action column
    $this->assertContains('ALTER COLUMN "action" SET NOT NULL', $text);
    // make sure SET NOT NULL is specified for description column
    $this->assertContains('ALTER COLUMN "description" SET NOT NULL', $text);

    // build base + strict + new table, check contents
    $addtable_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_b, $this->xml_file_c));
    pgsql8::build($this->output_prefix, $addtable_db_doc);
    $text = file_get_contents($this->output_prefix . '_build.sql');
    // make sure NOT NULL is specified for resolution column
    $this->assertContains('ALTER COLUMN "resolution" SET NOT NULL', $text);
    // make sure NOT NULL is NOT specified for points column
    $this->assertNotContains('ALTER COLUMN "points" SET NOT NULL', $text);
  }

  /**
   * @group mysql5
   * @dataProvider output_variance_provider
   */
  public function testFullBuildMysql5($output_file_dir, $output_file_prefix) {
    $this->apply_options_mysql5();
    $this->setup_mysql5($output_file_dir, $output_file_prefix);

    // build base full, check contents
    $base_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_a));
    mysql5::build($this->output_prefix, $base_db_doc);
    $text = file_get_contents($this->output_prefix . '_build.sql');
    // make sure NOT NULL is specified for action column
    $this->assertContains('`action` character varying(16) NOT NULL', $text);
    // make sure NOT NULL is NOT specified for description column
    $this->assertNotContains('`description` character varying(200) NOT NULL', $text);

    // build base + strict, check contents
    $strict_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_b));
    mysql5::build($this->output_prefix, $strict_db_doc);
    $text = file_get_contents($this->output_prefix . '_build.sql');
    // make sure NOT NULL is specified for action column
    $this->assertContains('`action` character varying(16) NOT NULL', $text);
    // make sure NOT NULL is specified for description column
    $this->assertContains('`description` character varying(200) NOT NULL', $text);

    // build base + strict + new table, check contents
    $addtable_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_b, $this->xml_file_c));
    mysql5::build($this->output_prefix, $addtable_db_doc);
    $text = file_get_contents($this->output_prefix . '_build.sql');
    // make sure NOT NULL is specified for resolution column
    $this->assertContains('`resolution` character varying(16) NOT NULL', $text);
    // make sure NOT NULL is NOT specified for points column
    $this->assertNotContains('`points` int NOT NULL', $text);
  }

  /**
   * @group pgsql8
   * @dataProvider output_variance_provider
   */
  public function testUpgradeNewTablePgsql8($output_file_dir, $output_file_prefix) {
    $this->apply_options_pgsql8();
    $this->setup_pgsql8($output_file_dir, $output_file_prefix);

    // upgrade from base 
    // to base + strict action table + new resolution table
    // check null specificity
    $base_db_doc = xml_parser::xml_composite(array($this->xml_file_a));
    $newtable_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_b, $this->xml_file_c));
    pgsql8::build_upgrade('', 'newtable_upgrade_test_pgsql8_base', $base_db_doc, array(), $this->output_prefix, 'newtable_upgrade_test_pgsql8_newtable', $newtable_db_doc, array());
    $text = file_get_contents($this->output_prefix . '_upgrade_single_stage.sql');
    // make sure NOT NULL is specified for description column
    $this->assertContains('ALTER COLUMN "description" SET NOT NULL', $text);
    // make sure NOT NULL is specified for resolution column
    $this->assertContains('ALTER COLUMN "resolution" SET NOT NULL', $text);
    // make sure NOT NULL is NOT specified for points column
    $this->assertNotContains('ALTER COLUMN "points" SET NOT NULL', $text);
  }

  /**
   * @group mysql5
   * @dataProvider output_variance_provider
   */
  public function testUpgradeNewTableMysql5($output_file_dir, $output_file_prefix) {
    $this->apply_options_mysql5();
    $this->setup_mysql5($output_file_dir, $output_file_prefix);

    // upgrade from base 
    // to base + strict action table + new resolution table
    // check null specificity
    $base_db_doc = xml_parser::xml_composite(array($this->xml_file_a));
    $newtable_db_doc = xml_parser::xml_composite(array($this->xml_file_a, $this->xml_file_b, $this->xml_file_c));
    mysql5::build_upgrade('', 'newtable_upgrade_test_pgsql8_base', $base_db_doc, array(), $this->output_prefix, 'newtable_upgrade_test_pgsql8_newtable', $newtable_db_doc, array());
    $text = file_get_contents($this->output_prefix . '_upgrade_single_stage.sql');
    // make sure NOT NULL is specified for description column
    $this->assertContains('`description` character varying(200) NOT NULL', $text);
    // make sure NOT NULL is specified for resolution column
    $this->assertContains('`resolution` character varying(16) NOT NULL', $text);
    // make sure NOT NULL is NOT specified for points column
    $this->assertNotContains('`points` int NOT NULL', $text);
  }

}
