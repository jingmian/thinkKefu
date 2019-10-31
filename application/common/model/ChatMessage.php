<?php

namespace app\common\model;

use think\Model;

class ChatMessage extends Model
{	
	protected $createTime = 'create_time';
	protected $updateTime = false;
  
  	public function receiver(){
  		return $this->belongsTo('Users','to_uid','id')->field('id,nick_name,avatar');
  	}

  	public function sender(){
  		return $this->belongsTo('Users','from_uid','id')->field('id,nick_name,avatar');
  	}
}
