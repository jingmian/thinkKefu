<?php
// +----------------------------------------------------------------------
// | EfarHost [ Easy Far And Easy More ... ]
// +----------------------------------------------------------------------
// | Copyright (c) 2012~2018 http://efarhost.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 琴瑟琵琶 <efarhost@foxmail.com>
// +----------------------------------------------------------------------
// | [GatewayWorker事件处理]
// +----------------------------------------------------------------------

namespace app\worker\controller;

use GatewayWorker\Lib\Gateway;
use think\facade\Cache;
use think\facade\Session;
use Workerman\Lib\Timer;
use Workerman\Worker;

class GatewayEvents
{
  /**
   * onWorkerStart 事件回调
   * 当gateway服务启动时
   *
   * @param  integer  $client_id 断开连接的客户端client_id
   * @param  mixed    $data
   * @return void
   */
  public static function onWorkerStart(Worker $businessWorker)
  {
    // 定时统计数据
    if (0 === $businessWorker->id) {
      // 1分钟统计一次实时数据
      Timer::add(60 * 1, function () {
        self::writeLog(1);
      });

      // 30分钟统计全天数据
      Timer::add(60 * 30, function () {
        self::writeLog(2);
      });
    }
  }

  /**
   * onWebSocketConnect 事件回调
   * 当客户端连接上gateway完成websocket握手时触发
   *
   * @param  integer  $client_id 断开连接的客户端client_id
   * @param  mixed    $data
   * @return void
   */
  public static function onWebSocketConnect($client_id, $data)
  {
    $response = [
      'status'       => 200,
      'message'      => 'Everything Is Ready Except The East Wind!',
      'message_type' => 'connected',
      'data'         => ['client_id' => $client_id],
    ];

    Gateway::sendToCurrentClient(json_encode($response));
  }

  /**
   * onMessage 事件回调
   * 当客户端发来数据(Gateway进程收到数据)后触发
   *
   * @access public
   * @param  int       $client_id
   * @param  mixed     $data
   * @return void
   */
  public static function onMessage($client_id, $message)
  {

    try {
      $message  = json_decode($message, true);
      $response = self::messageProcessor($client_id, $message);
      Gateway::sendToClient($client_id, json_encode($response));

    } catch (Exception $e) {
      $response = [
        'status'       => 412,
        'message'      => $e->getMessage(),
        'message_type' => 'error',
        'data'         => [],
      ];
    }

  }

  /**
   * onClose 事件回调 当用户断开连接时触发的方法
   *
   * @param  integer $client_id 断开连接的客户端client_id
   * @return void
   */
  public static function onClose($client_id)
  { 

    $leaveUser =  Cache::pull($client_id);
    $leaveUid =  isset($leaveUser['id'])?$leaveUser['id']:null;
    //$leaveUid = Gateway::getUidByClientId($client_id);
    $response = [
      'status'       => 412,
      'message'      => 'Client:['. $client_id . '] Logout',
      'message_type' => 'logout',
      'data'         => [
        'client_id' => $client_id,
        'bindUid'  =>  $leaveUid
      ],
    ];

    \app\common\model\Users::where('id', $leaveUid)->setField('online', 0);

    GateWay::sendToAll(json_encode($response));
  }

  /**
   * onWorkerStop 事件回调
   * 当businessWorker进程退出时触发。每个进程生命周期内都只会触发一次。
   *
   * @param  \Workerman\Worker    $businessWorker
   * @return void
   */
  public static function onWorkerStop(Worker $businessWorker)
  {
    echo "WorkerStop\n";
  }

  private static function writeLog($flag = 1){



  }

  /**
   * [messageProcessor 消息处理器]
   * @param  [type] $client_id [description]
   * @param  [type] $request   [description]
   * @return [type]            [description]
   */
  private static function messageProcessor($client_id, $request)
  {
    $response = [];
    switch ($request['message_type']) {
      case 'init':
        $customer = \app\common\model\Users::where('_token', $request['token'])->field('id,username,nick_name,avatar')->find();
        if (!$customer) {
          $response = [
            'status'       => 404,
            'message'      => 'User Not Find Or Token TimeOut',
            'message_type' => 'error',
            'data'         => [],
          ];

          return $response;

        } else {
          $response = [
            'status'       => 200,
            'message'      => 'Initialization Succeed',
            'message_type' => 'init',
            'data'         => $customer,
          ];

        }
        $customer['is_kefu'] = 0;
        Session::set('ClientUserInfo', $customer);
        Cache::set($client_id,  $customer);

        // 绑定 client_id 和 uid
        Gateway::bindUid($client_id, $customer['id']);

        $usersList = Cache::get('workerUsersList');

        if (array_key_exists($customer['id'], (array) $usersList)) {
          $usersList[$customer['id']]['client_id'] = $client_id;
        } else {
          $usersList[$customer['id']] = $customer;
        }
        
        Cache::set('workerUsersList', $usersList);
        \app\common\model\Users::where('id', $customer['id'])->setField('online', 1);
        unset($customer, $usersList);

        return $response;
        break;
      case 'kefuInit':

        $kefuInfo = \app\common\model\Users::where('id', $request['service_uid'])
          ->field('id,username,nick_name,avatar,is_kefu')
          ->find();

        if (!$kefuInfo) {
          $response = [
            'status'       => 404,
            'message'      => 'User Not Find ',
            'message_type' => 'error',
            'data'         => [],
          ];

          return $response;

        } else {
          $response = [
            'status'       => 200,
            'message'      => 'Initialization Succeed',
            'message_type' => 'kefuInit',
            'data'         => $kefuInfo,
          ];

        }
        Gateway::bindUid($client_id, $kefuInfo['id']);   
        Cache::set($client_id,  $kefuInfo);
        \app\common\model\Users::where('id', $kefuInfo['id'])->setField('online', 1);
        Session::set('ClientUserInfo', $kefuInfo);


        $kefuList = Cache::get('workerKefuList');
        if (array_key_exists($kefuInfo['id'], (array) $kefuList)) {
          $kefuList[$kefuInfo['id']]['client_id'] = $client_id;
        } else {
          $kefuList[$kefuInfo['id']] = [
            'id'        => $kefuInfo['id'],
            'username'  => $kefuInfo['username'],
            'nickname'  => $kefuInfo['nick_name'],
            'avatar'    => '' . $kefuInfo['avatar'],
            'group'     => $kefuInfo['is_kefu'],
          ];

        }
        Cache::set('workerKefuList', $kefuList);

        unset($kefuInfo, $kefuList);

        return $response;
        break;
      case 'ping':
        $response = [
          'type' => 'pong',
        ];
        return $response;
        break;

      case 'chatMessage':

      
        $userInfo =Session::get('ClientUserInfo');

        if (empty($request['data']['from_uid'])) {
          $request['data']['from_uid'] = $userInfo['id'];
        }

        $request['data']['create_time'] = time();

        if (!in_array($request['data']['model'], ['text', 'image', 'audio', 'video'])) {
          $request['data']['content'] = json_encode($request['data']);
          unset($request['data']['id']);
        }

        if ($result = \app\common\model\ChatMessage::create($request['data'], true)) {
          $messageCustomerList = Cache::get('workerMessageSenderList');
          if (!in_array($request['data']['from_uid'], (array) $messageCustomerList) && !$userInfo['is_kefu']) {
            $messageCustomerList[] = $request['data']['from_uid'];
            $response              = [
              'status'       => 200,
              'message'      => 'Customer Consult',
              'message_type' => 'visitorConnect',
              'data'         => $result->append(['receiver', 'sender'])->toArray(),
            ];
            GateWay::sendToUid($request['data']['to_uid'], json_encode($response));

            Cache::set('workerMessageSenderList', $messageCustomerList);
          }

          $response = [
            'status'       => 200,
            'message'      => 'Message sended',
            'message_type' => 'chatMessage',
            'data'         => $result->append(['receiver', 'sender'])->toArray(),
          ];
          GateWay::sendToUid($request['data']['to_uid'], json_encode($response));

          $response = [
            'status'       => 200,
            'message'      => 'Message sended',
            'message_type' => 'sendSucceed',
            'data'         => $result->append(['receiver', 'sender'])->toArray(),
          ];
          unset($userInfo);

        } else {

          $response = [
            'status'       => 500,
            'message'      => 'Insert Database Error',
            'message_type' => 'error',
            'data'         => $request['data'],
          ];
        }

        return $response;
        break;

      default:
        $response = [
          'status'       => 412,
          'message'      => 'message_type Undefined',
          'message_type' => 'error',
          'data'         => [],
        ];
        break;
    }

    return $response;
  }

}
