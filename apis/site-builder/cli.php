<?php

namespace SiteBuilder;

class CLI {
    public static function build($name) {
        $builder = new Builder($name);
    }
}
