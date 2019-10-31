<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Base extends Controller
{
   public function initialize(){

    parent::initialize();

    if (!Session::has('administrator')) {
        $this->redirect('Login/index');
    }
   }
}
