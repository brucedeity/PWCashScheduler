<?php

class Logger {

    private
        $file,
        $timestamp;

    public function __construct($filename) {
        $this->file = $filename;
    }

    public function putLog($content) {
        file_put_contents($this->file, date('d/m/Y H:i:s - ').$content.PHP_EOL, FILE_APPEND);
    }
}