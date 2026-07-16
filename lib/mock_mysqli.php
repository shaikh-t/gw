<?php
/**
 * lib/mock_mysqli.php
 * A lightweight mock of mysqli to allow local page rendering and testing when the database is unavailable.
 */

class MockMySQLi {
    public $connect_errno = 0;
    public $connect_error = '';
    public $error = '';
    public $insert_id = 1;

    public function set_charset($charset) {
        return true;
    }

    public function query($sql) {
        return new MockMySQLiResult();
    }

    public function prepare($sql) {
        return new MockMySQLiStmt($sql);
    }

    public function real_escape_string($str) {
        return addslashes($str);
    }
}

class MockMySQLiResult {
    public $num_rows = 0;

    public function fetch_assoc() {
        return null;
    }

    public function fetch_row() {
        return null;
    }

    public function free() {
        return true;
    }
}

class MockMySQLiStmt {
    private $sql;
    public $insert_id = 1;
    public $error = '';

    public function __construct($sql) {
        $this->sql = $sql;
    }

    public function bind_param(...$args) {
        return true;
    }

    public function execute() {
        return true;
    }

    public function get_result() {
        return new MockMySQLiResult();
    }

    public function bind_result(&...$args) {
        return true;
    }

    public function fetch() {
        return null;
    }

    public function close() {
        return true;
    }
}
