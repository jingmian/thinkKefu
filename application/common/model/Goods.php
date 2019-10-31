<?php

namespace app\common\model;

use think\Model;

class Goods extends Model
{
    public function shopInfo(){

    	return $this->belongsTo('Shops','shop_id','id')->field('id,shop_name,shop_descript,shop_logo');
    }
}
