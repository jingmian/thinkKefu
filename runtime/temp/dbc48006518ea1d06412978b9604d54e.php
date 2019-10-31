<?php /*a:1:{s:72:"D:\Projects\Efarsoft\thinkKefu\application\service\view\index\index.html";i:1572120490;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>共享仓超客服工作台</title>
    <link rel="stylesheet" href="/static/service/js/layui/css/layui.css">
    <link rel="stylesheet" href="/static/service/css/whisper.css">
</head>
<body class="layui-layout-body">
<div class="layui-layout layui-layout-admin">
    <div class="layui-header">
        <div class="layui-logo" style="color: white">共享仓超客服【工作台】</div>
        <ul class="layui-nav layui-layout-right">
            <li style="margin-top: 10%">
                <a href="javascript:;" onclick="loginOut();">
                    <button class="layui-btn layui-bg-red">
                        <i class="layui-icon">&#xe609;</i> 退出下班
                    </button>
                </a>
            </li>
        </ul>
    </div>

    <div class="layui-side" style="background:#f2f2f2">
        <div class="layui-side-scroll">
            <blockquote class="layui-elem-quote layui-bg-cyan" style="color: white">正在咨询的会员</blockquote>
            <ul class="layui-unselect" id="user_list">

            </ul>
        </div>
    </div>

    <div class="layui-body" style="bottom:0">
        <input type="hidden" id="active-user" data-avatar="" data-name="" data-id=""><!-- 当前对话的用户 -->
        <div class="chat-left">
            <div class="chat-box whisper-chat-main">

            </div>
            <div class="msg-send">
                <div class="tools-bar">
                    <i class="layui-icon" style="font-size: 30px;" id="face">&#xe60c;</i>
                    <i class="layui-icon" style="font-size: 30px;" id="image">&#xe60d;</i>
                    <i class="layui-icon" style="font-size: 30px;" id="file">&#xe61d;</i>
                </div>
                <div class="msg-box">
                    <textarea class="msg-area" id="msg-area"></textarea>
                </div>
                <div class="send-area">
                    <span style="margin-left:10px;color:gray">快捷键 Enter</span>
                    <button class="layui-btn layui-btn-small layui-bg-cyan" style="float:right;margin-right:10px;height: 40px;padding: 0 15px;" id="send">
                        <i class="layui-icon">&#xe609;</i>发送
                    </button>
                </div>
            </div>
        </div>

        <div style="width:28%;height:100%;float:left;margin-left:1%">
            <div class="layui-tab layui-tab-brief" lay-filter="docDemoTabBrief">
                <ul class="layui-tab-title">
                    <li class="layui-this">访客信息</li>
                    <li>常用语</li>
                </ul>
                <div class="layui-tab-content" style="height: 100px;">
                    <div class="layui-tab-item layui-show">
                        <div class="layui-form layui-form-pane">
                            <div class="layui-form-item">
                                <label class="layui-form-label">访客名</label>
                                <div class="layui-input-block">
                                    <input type="text" id="f-user" class="layui-input" readonly>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">IP</label>
                                <div class="layui-input-block">
                                    <input type="text" id="f-ip" class="layui-input" readonly>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">地区</label>
                                <div class="layui-input-block">
                                    <input type="text" id="f-area" class="layui-input" readonly>
                                </div>
                            </div>
                            <?php if((1 == $status['change_status'])): ?>
                            <div class="layui-form-item">
                                <label class="layui-form-label layui-bg-cyan" style="cursor: pointer;color:white" id="scroll-link">转接</label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="layui-tab-item">
                        <div class="layui-form">
                            <table class="layui-table">
                                <thead>
                                <tr>
                                    <th>内容</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if(is_array($word) || $word instanceof \think\Collection || $word instanceof \think\Paginator): if( count($word)==0 ) : echo "" ;else: foreach($word as $key=>$vo): ?>
                                <tr>
                                    <td><?php echo htmlentities($vo['content']); ?></td>
                                    <td>
                                        <a href="javascript:;" onclick="sendWord(this)" data-word="<?php echo htmlentities($vo['content']); ?>" style="color:#009688">应用</a>
                                    </td>
                                </tr>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 转接提示层 -->
<div class="layui-form" id="change-box" style="display: none">
    <div class="layui-form-item" style="margin-top: 20px">
        <label class="layui-form-label">选择分组</label>
        <div class="layui-input-block" style="width: 70%" >
            <select lay-verify="required" lay-filter="group">
                <option value=""></option>
                <?php if(!empty($groups)): if(is_array($groups) || $groups instanceof \think\Collection || $groups instanceof \think\Paginator): if( count($groups)==0 ) : echo "" ;else: foreach($groups as $key=>$vo): ?>
                <option value="<?php echo htmlentities($vo['id']); ?>"><?php echo htmlentities($vo['name']); ?></option>
                <?php endforeach; endif; else: echo "" ;endif; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>
</div>
<!-- 转接提示层 -->

<script src="http://libs.baidu.com/jquery/2.0.0/jquery.min.js"></script>
<script src="/static/service/js/layui/layui.js"></script>
<script>
    var servicer = {
        "uid":"<?php echo htmlentities($servicer['uid']); ?>",
        'nickname':"<?php echo htmlentities($servicer['nickname']); ?>",
        'avatar':"<?php echo htmlentities($servicer['avatar']); ?>",
        "group":"<?php echo htmlentities($servicer['group']); ?>"
    } ;

    var socket_server = "<?php echo htmlentities($config['socket']); ?>";
</script>


<script type="text/javascript" src="/static/service/js/thinkKefu.js"></script>
</body>
</html>