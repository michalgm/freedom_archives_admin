<?php
/*
* Database MySQLDump Class File
* Copyright (c) 2009 by James Elliott
* James.d.Elliott@gmail.com
* GNU General Public License v3 http://www.gnu.org/licenses/gpl.html
*
*/

class MySQLDump
{

    // This can be set both on constructor or manually
    public $host;
    public $user;
    public $pass;
    public $db;
	public $output;

    // Usable switch
    public $droptableifexists = false;

    //compress
    public $compress = false;

    // Internal stuff
    private $tables = array();
    private $views = array();
    private $db_handler;
    /**
     * Constructor of MySQLDump
     *
     * @param string $db        Database name
     * @param string $user      MySQL account username
     * @param string $pass      MySQL account password
     * @param string $host      MySQL server to connect to
     * @return null
     */
    public function __construct($db = '', $user = '', $pass = '', $host = 'localhost')
    {
        $this->db = $db;
        $this->user = $user;
        $this->pass = $pass;
        $this->host = $host;
    }

    /**
     * Main call
     *
     * @param string $filename  Name of file to write sql dump to
     * @return bool
     */
    public function start()
    {
        // Connecting with MySQL
        try {
            $this->db_handler = new PDO("mysql:dbname={$this->db};host={$this->host}", $this->user, $this->pass);
        } catch (PDOException $e) {
            throw new Exception("Connection to MySQL failed with message: " . $e->getMessage(), 3);
        }
        // Fix for always-unicode output
        $this->db_handler->exec("SET NAMES utf8");
        // https://github.com/clouddueling/mysqldump-php/issues/9
        $this->db_handler->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
        // Formating dump file
        $this->writeHeader();
        // Listing all tables from database
        $this->tables = array();
        foreach ($this->db_handler->query("SHOW TABLES") as $row) {
            array_push($this->tables, current($row));
        }
        // Exporting tables one by one
        foreach ($this->tables as $table) {
            $is_table = $this->getTableStructure($table);
            if ($is_table == true) {
                $this->listValues($table);
            }
        }
        foreach ($this->views as $view) {
            $this->write($view);
        }
		return $this->output;
    }

    /**
     * Output routine
     *
     * @param string $string  SQL to write to dump file
     * @return bool
     */
    private function write($string)
    {
		$this->output .= $string;
    }

    /**
     * Writting header for dump file
     *
     * @return null
     */
    private function writeHeader()
    {
        // Some info about software, source and time
        $this->write("-- mysqldump-php SQL Dump\n");
        $this->write("-- https://github.com/clouddueling/mysqldump-php\n");
        $this->write("--\n");
        $this->write("-- Host: {$this->host}\n");
        $this->write("-- Generation Time: " . date('r') . "\n\n");
        $this->write("--\n");
        $this->write("-- Database: `{$this->db}`\n");
        $this->write("--\n\n");
    }

    /**
     * Table structure extractor
     *
     * @param string $tablename  Name of table to export
     * @return null
     */
    private function getTableStructure($tablename)
    {
        foreach ($this->db_handler->query("SHOW CREATE TABLE `$tablename`") as $row) {
            if (isset($row['Create Table'])) {
                $this->write("-- --------------------------------------------------------\n\n");
                $this->write("--\n-- Table structure for table `$tablename`\n--\n\n");
                if (true === $this->droptableifexists) {
                    $this->write("DROP TABLE IF EXISTS `$tablename`;\n\n");
                }
                $this->write($row['Create Table'] . ";\n\n");
                return true;
            }
            if (isset($row['Create View'])) {
                $view  = "-- --------------------------------------------------------\n\n";
                $view .= "--\n-- Table structure for view `$tablename`\n--\n\n";
                $view .= $row['Create View'] . ";\n\n";
                $this->views[] = $view;
                return false;
            }
        }
    }

    /**
     * Table rows extractor
     *
     * @param string $tablename  Name of table to export
     * @return null
     */
    private function listValues($tablename)
    {
        $this->write("--\n-- Dumping data for table `$tablename`\n--\n\n");
        foreach ($this->db_handler->query("SELECT * FROM `$tablename`", PDO::FETCH_NUM) as $row) {
            $vals = array();
            foreach ($row as $val) {
                $vals[] = is_null($val) ? "NULL" : $this->db_handler->quote($val);
            }
            $this->write("INSERT INTO `$tablename` VALUES(" . implode(", ", $vals) . ");\n");
        }
        $this->write("\n");
    }
}
?>
