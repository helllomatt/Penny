<?php

namespace PennyCLI;

class Setup {
    public static function init() {
        passthru("composer dump-autoload -o");

        if (!file_exists("./config.json")) {
            file_put_contents("./config.json", "");
        }

        $themename = ThemeQuickstart::new_theme(true);
        SiteQuickstart::new_site(true, $themename);
    }
}
