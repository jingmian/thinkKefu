<?php
namespace app\index\controller;
use think\Controller;
class Chatlog extends Controller
{   

    protected $userInfo;
    public function initialize(){
        $token = input('token','');
        $this->userInfo = \app\common\model\Users::where('_token',$token)->find();
        if (!$this->userInfo) {
          die(json_encode(['status'=>1010]));
        }
        parent::initialize();
    }
    public function lists(){

        $msgModel = new \app\common\model\ChatMessage;
	    $user_id = $this->userInfo['id'];
        $list     = $msgModel::where('id', 'IN', function ($query) use ($user_id) {
            $query->name('chat_message')
                ->where('from_uid|to_uid', $user_id)
                ->field('max(id) ')
                ->group('concat(if(from_uid>to_uid,from_uid,to_uid),if(from_uid<to_uid,from_uid,to_uid))');
        })->field('id,from_uid,to_uid,model,type,content,create_time')->where('removed',0)->order('id DESC')->paginate();

        foreach ($list as $k => $v) {
            if ($v->from_uid != $user_id) {
               $list[$k]['sender'] = \app\common\model\Users::where('id',$v->from_uid)->field('id,nick_name,avatar')->find();
            } else {
               $list[$k]['sender'] = \app\common\model\Users::where('id',$v->to_uid)->field('id,nick_name,avatar')->find();
            }
        }
        return json(['error_code'=>0,'message'=>'获取成功','data'=>$list]) ;

    }
    //聊天记录详情
    public function detail()
    {   

        $formUid= input('from_uid',0,'intval');
        $slefUid = $this->userInfo['id'];
        $result = \app\common\model\ChatMessage::where(function($query)use($formUid,$slefUid){
            $query->where('from_uid',$formUid)->where('to_uid',$slefUid);

        })->whereOr(function($query)use($formUid,$slefUid){
            $query->where('from_uid',$slefUid)->where('to_uid',$formUid);
        })
        ->with('receiver,sender')
        ->order('id DESC')  
        ->paginate(10);

        foreach ($result as $key => $value) {
           if (!in_array($value['model'], ['text','image','audio','video'])) {
                $content =  json_decode($value['content'],true);
                $result[$key] = array_merge($value->toArray(),$content) ;
           }
        }


        return json(['error_code'=>0,'message'=>'获取成功','data'=>$result]);
    }
    //移除会话列表
    public function remove(){
        $id = input('id',0,'intval');
        if ( \app\common\model\ChatMessage::where('id',$id)->setField('removed',1)) {
            return json(['error_code'=>0,'message'=>'移除会话成功','data'=>['last_id'=>$id]]);
        }
           

    }

   
}



