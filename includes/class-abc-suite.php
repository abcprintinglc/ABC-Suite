<?php

class ABC_Suite {
    /**
     * @return array{loaded:string[],registered:string[],missing_files:string[],missing_classes:string[],module_errors:array<int,string>}
     */
    public function boot(): array {
        return ABC_Module_Loader::load_and_register();
    }
}
