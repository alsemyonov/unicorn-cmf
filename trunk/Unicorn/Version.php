<?php

final class Unicorn_Version {
    const VERSION = '0.1.0';
    static function compareVersion($version) {
        return version_compare($version, self::VERSION);
    }
}