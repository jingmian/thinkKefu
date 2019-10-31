<?php
namespace app\service\controller;

use think\Controller;
use think\facade\Cookie;

class Base extends Controller
{
    public function initialize()
    {   
        parent::initialize();
        $servicer =Cookie::get('servicer');
        if(!$servicer['id']){
            $this->redirect('login/index');
        }
        $config = [
            'version' => 'V1.0 Beta',
            'socket' => 'chat.lewujie.com:8800'
        ];
        $this->servicer = $servicer;
        $this->assign(['config'=>$config,'servicer'=>$servicer]);

    }
}