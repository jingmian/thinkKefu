<?php

namespace app\common\model;

use think\Model;

class Users extends Model
{
  protected function getAvatarAttr($value){
  	return $value?'https://image.ynjjyy.cn'.$value:'';
  }

  // protected function getOnlineStatusAttr($value,$data){
  // 	return $data['online']?'online':'offline';
  // }

  public function shopInfo(){
  	return $this->hasOne('Shops','shop_id','id')->field('id,shop_name,shop_descript,shop_logo');
  }


}
