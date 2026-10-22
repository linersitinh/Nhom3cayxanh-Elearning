<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('elearn:about', function () {
    $this->info('E-Learn Laravel + MySQL | Nhóm 3cayxanh');
})->purpose('Show E-Learn application information');
