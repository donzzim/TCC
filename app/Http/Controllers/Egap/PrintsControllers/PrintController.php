<?php

namespace App\Http\Controllers\Egap\PrintsControllers;

use App\Http\Controllers\Controller;

abstract class PrintController extends Controller
{
    abstract function print(int $id);
}
