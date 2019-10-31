<?php
namespace app\index\controller;
use think\Controller;
class Index extends Controller
{
    public function index()
    {
        $this->redirect('service/Index/index');
    }

    public function getShopKefu(){

    	// $kefuList = \app\common\model\Users::where('is_kefu','>',0)->field('id as kefu_uid ,username,nick_name,avatar')->select();

    	// $kefuList = $kefuList->toArray();

    	// $kefu =  $kefuList[array_rand($kefuList)]; 
        
            $kefu = \app\common\model\Users::where('id',2064)->field('id as kefu_uid ,username,nick_name,avatar')->find(); 
        return json([ 'error_code'=>0,'message'=>'Succeed','data'=>$kefu]);

    	// $shopId =     input('shop_id');
    	// $ownerInfo = \app\common\model\Users::where('shop_id',$shopId)->field('id as kefu_uid,nick_name,avatar')->find();
    	

    }
}
