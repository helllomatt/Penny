<?php

namespace PennyCLI;

class Setup {
    public static function init() {
        passthru("composer dump-autoload -o");
        $themename = ThemeQuickstart::new_theme(true);
        SiteQuickstart::new_site(true, $themename);
    }
}
