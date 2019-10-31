<?php
namespace app\service\controller;
use think\Controller;
use think\facade\Cookie;

class Login extends Controller
{
    public function index()
    {
        if ($this->request->isPost()) {
            $username = input('post.username');
            $password = input('post.password');
            if (empty($username) ||empty($password) ) {
                $this->error('账号和密码不能为空');
            }
            $user = \app\common\model\Users::where('username', $username)->where('is_kefu',1)->find();
            if ($user->isEmpty()) {
              $this->error('客服账号不存在');
            }
            if (jjyy_pwd($password) != $user['password']) {
              $this->error('登录密码错误');
            }
            $servicer = $user->visible(['id','username','nick_name','avatar','is_kefu'])->toArray();
            Cookie::set('servicer', $servicer);
            $this->success('登录成功,正在跳转首页~','Index/index',$servicer);

        }else{
            return $this->fetch();
        }
        
    }

    public function loginOut()
    {
        cookie('servicer', null);
        $this->redirect(url('login/index'));
    }
}