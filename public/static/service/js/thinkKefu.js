const socket = new WebSocket('wss://chat.lewujie.com/wss');
const thisServicer =   layui.data('servicePlatform').servicer;

// 初始化Socket数据 
socket.onopen = function (res) {
    layer.msg('链接成功', {time: 1000});
    var initData = {
        'message_type':'kefuInit',
        'service_uid':thisServicer.id
    }
    socket.send(JSON.stringify(initData));
};

// 监听消息
socket.onmessage = function (res) {
    var data = eval("(" + res.data + ")");
    console.log(data);
    switch (data['message_type']) {
        // 服务端ping客户端
        case 'error':          
            layer.msg('data.message', {time: 1000});    
            break;
         case 'ping':
            socket.send('{"type":"ping"}');
            break;
        // 添加用户
        case 'visitorConnect':
            visitorConnect(data.data.sender);
            break;
        // 移除访客到主面板
        case 'visitorLeave':
            visitorLeave(data.data);
            break;

        // 监测聊天数据
        case 'chatMessage':
            showUserMessage(data.data);
            break;
    }
};

// 监听失败
socket.onerror = function(err){
    layer.alert('连接失败,请联系管理员', {icon: 2, title: '错误提示'});
};

$(function () {

    // 获取服务用户列表
    $.getJSON('/service/index/getUserList', function(res){
        if(1 == res.code && res.data.length > 0){
            $.each(res.data, function(k, v){
                visitorConnect(v);
            });

            var id = $(".layui-unselect").find('li').eq(0).data('id');
            var name = $(".layui-unselect").find('li').eq(0).data('name');
            var avatar = $(".layui-unselect").find('li').eq(0).data('avatar');
            var ip = $(".layui-unselect").find('li').eq(0).data('ip');

            // 默认设置第一个用户为当前对话的用户
            $("#active-user").attr('data-id', id).attr('data-name', name).attr('data-avatar', avatar).attr('data-ip', ip);

            $(".layui-unselect").find('li').eq(0).addClass('active').find('span:eq(1)').removeClass('layui-badge').text('');
            $("#f-user").val(name);
            $("#f-ip").val(ip);

            $.getJSON('/service/index/getCity', {ip: ip}, function(res){
                $("#f-area").val(res.data);
            });

            // 拉取和这个人的聊天记录
            $("#u-" + id).show();
            getChatLog(id, 1);
        }
    });

    // 监听快捷键发送
    document.getElementById('msg-area').addEventListener('keydown', function (e) {
        if (e.keyCode != 13) return;
        e.preventDefault();  // 取消事件的默认动作
        sendMessage();
    });

    // 点击表情
    var index;
    $("#face").click(function (e) {
        e.stopPropagation();
        layui.use(['layer'], function () {
            var layer = layui.layer;

            var isShow = $(".layui-whisper-face").css('display');
            if ('block' == isShow) {
                layer.close(index);
                return;
            }
            var height = $(".chat-box").height() - 110;
            layer.ready(function () {
                index = layer.open({
                    type: 1,
                    offset: [height + 'px', $(".layui-side").width() + 'px'],
                    shade: false,
                    title: false,
                    closeBtn: 0,
                    area: '395px',
                    content: showFaces()
                });
            });
        });
    });

    $(document).click(function (e) {
        layui.use(['layer'], function () {
            var layer = layui.layer;
            if (isShow) {
                layer.close(index);
                return false;
            }
        });
    });

    // 发送消息
    $("#send").click(function () {
        sendMessage();
    });

    // hover用户
    $(".layui-unselect li").hover(function () {
        $(this).find('i').show();
    }, function () {
        $(this).find('i').hide();
    });

    // 关闭用户
    $('.close').click(function () {
        var uid = $(this).parent().data('id');
        $(this).parent().remove(); // 清除左侧的用户列表
        $('#u-' + uid).remove(); // 清除右侧的聊天详情
    });

    // 检测滚动，异步加载更多聊天数据
    $(".chat-box").scroll(function () {
        var top = $(".chat-box").scrollTop();
    });

    // 会员转接
    $("#scroll-link").click(function(){

        var id = $("#active-user").attr('data-id');
        var name = $("#active-user").attr('data-name');
        var avatar = $("#active-user").attr('data-avatar');
        var ip = $("#active-user").attr('data-ip');

        if(id == '' || name == ''){
            layer.msg("请选择要转接的会员");
        }

        // 二次确认
        var layerIndex = null;
        layerIndex = layer.confirm('确定转接 ' + name + ' ？', {
            title: '转接提示',
            closeBtn: 0,
            icon: 3,
            btn: ['确定', '取消'] // 按钮
        }, function(){
            layer.close(layerIndex);
            layerIndex = layer.open({
                title: '',
                type: 1,
                area: ['30%', '40%'],
                content: $("#change-box")
            });

            // 监听选择
            layui.use(['form'], function(){
                var form = layui.form;

                form.on('select(group)', function (data) {
                    if(uinfo.group == data.value){
                        layer.msg("已经在该分组，不需要转接！");
                    }else{

                        layer.close(layerIndex);
                        var group = data.value; // 分组
                        // 交换分组
                        var change_data = '{"type":"changeGroup", "uid":"' + id + '", "name" : "' + name + '", "avatar" : "'
                            + avatar + '", "group": ' + group + ', "ip" : "' + ip + '"}';

                        //console.log(change_data);
                        socket.send(change_data);

                        // 将该会员从我的会话中移除
                        visitorLeave({id: id});

                        layer.msg('转接成功');
                    }
                });
            });

        }, function(){

        });
    });

     //全屏
    $("#fullScreen").on("click",function(){
        fullScreen();
    })
    //退出全屏
    $("#exitFullScreen").on("click",function(){
        exitFullscreen();
    })



});

var isShow = false;

layui.use(['element', 'form'], function () {
    var element = layui.element;
    var form = layui.form;
});

// 图片 文件上传
layui.use(['upload', 'layer'], function () {
    var upload = layui.upload;
    var layer = layui.layer;

    // 执行实例
    var uploadInstImg = upload.render({
        elem: '#image' // 绑定元素
        , accept: 'images'
        , exts: 'jpg|jpeg|png|gif'
        , url: '/service/upload/uploadImg' // 上传接口
        , done: function (res) {
            msg = {
                "model":'image',
                "content":res.data.src
            }
            sendMessage(msg);
            showBigPic();
        }
        , error: function () {
            // 请求异常回调
        }
    });

    var uploadInstFile = upload.render({
        elem: '#file' // 绑定元素
        , accept: 'file'
        , exts: 'zip|rar'
        , url: '/service/upload/uploadFile' // 上传接口
        , done: function (res) {
            sendMessage('file(' + res.data.src + ')[' + res.msg + ']');
        }
        , error: function () {
            // 请求异常回调
        }
    });
});

// 展示表情数据
function showFaces() {
    isShow = true;
    var alt = getFacesIcon();
    var _html = '<div class="layui-whisper-face"><ul class="layui-clear whisper-face-list">';
    layui.each(alt, function (index, item) {
        _html += '<li title="' + item + '" onclick="checkFace(this)"><img src="/static/libs/layui/images/face/' + index + '.gif" /></li>';
    });
    _html += '</ul></div>';

    return _html;
}

// 选择表情
function checkFace(obj) {
    var word = $(".msg-area").val() + ' face' + $(obj).attr('title') + ' ';
    $(".msg-area").val(word).focus();
}

// 发送消息
function sendMessage(sendMsg) {
    var msg = (typeof(sendMsg) == 'undefined') ? $(".msg-area").val() : sendMsg;
    if ('' == msg) {
        layui.use(['layer'], function () {
            var layer = layui.layer;
            return layer.msg('请输入回复内容', {time: 1000});
        });
        return false;
    }

    if (typeof(msg) == 'string') {
        msg ={
            'model':"text",
            'content':msg
        }

    }
    var word = msgFactory(servicer, 'mine', msg);

    var uid = $("#active-user").attr('data-id');
    var uname = $("#active-user").attr('data-name');

    socket.send(JSON.stringify({
        message_type: 'chatMessage',
        data: {'from_uid':thisServicer.id,'to_uid': uid,  'content': msg.content,'type':0,'model':msg.model}
    }));

    $("#u-" + uid).append(word);
    $(".msg-area").val('');
    // 滚动条自动定位到最底端
    wordBottom();
}

// 展示客服发送来的消息
function showUserMessage(data) {
    if ($('#f-' + data.sender.id).length == 0) {
        visitorConnect(data);
    }
    // 未读条数计数
    if (!$('#f-' + data.sender.id).hasClass('active')) {
        var num = $('#f-' + data.sender.id).find('span:eq(1)').text();
        if (num == '') num = 0;
        num = parseInt(num) + 1;
        $('#f-' + data.sender.id).find('span:eq(1)').removeClass('layui-badge').addClass('layui-badge').text(num);
    }

    var word = msgFactory(data.sender, 'user',data);
    setTimeout(function () {
        console.log()
        $("#u-" + data.sender.id).append(word);
        // 滚动条自动定位到最底端
        wordBottom();

        showBigPic();
    }, 200);
}

// 消息发送工厂
function msgFactory(userInfo, type,data) {
    var _html = '';
    if ('mine' == type) {
        _html += '<li class="whisper-chat-mine">';
    } else {
        _html += '<li>';
    }
    _html += '<div class="whisper-chat-user">';
    _html += '<img src="' + userInfo.avatar + '">';
    if ('mine' == type) {
        _html += '<cite><i>' + getDate() + '</i>' +userInfo.nick_name + '</cite>';
    } else {
        _html += '<cite>' + userInfo.nick_name + '<i>' + getDate() + '</i></cite>';
    }
    _html += '</div><div class="whisper-chat-text">' + replaceContent(data) + '</div>';
    _html += '</li>';

    return _html;
}

// 转义聊天内容中的特殊字符
function replaceContent(message) {
    switch(message.model) {

     case 'text':
        content =  message.content;
        break;
     case 'image':

        var preg =  /(http|https):\/\/([\w.]+\/?)\S*/;
        if (!preg.test(  message.content)) {
            message.content = 'https://image.ynjjyy.cn'+message.content;

        }
        content = '<img class="layui-think-photos" src="' + message.content + '">';
        break;
    case 'audio':
        content = '<audio  controls="controls" src="' + message.content + '" controls="controls"></audio>';
        break;
    case 'video':
        content = '<video  controls="controls" src="' + message.content + '" controls="controls"></video>';
        break;
     default:
        content = JSON.parse(message.content);
        content = '<iframe src="' + content.url + '" width="360px" height="640px">';
} 
  

    return content;
};


// 获取日期
function getDate() {
    var d = new Date(new Date());

    return d.getFullYear() + '-' + digit(d.getMonth() + 1) + '-' + digit(d.getDate())
        + ' ' + digit(d.getHours()) + ':' + digit(d.getMinutes()) + ':' + digit(d.getSeconds());
}

//补齐数位
var digit = function (num) {
    return num < 10 ? '0' + (num | 0) : num;
};

// 滚动条自动定位到最底端
function wordBottom() {
    var box = $(".chat-box");
    box.scrollTop(box[0].scrollHeight);
}

// 切换在线用户
function changeUserTab(obj) {
    obj.addClass('active').siblings().removeClass('active');
    wordBottom();
}

// 添加用户到面板
function visitorConnect(data) {

    var _html = '<li class="layui-nav-item" data-id="' + data.sender.id + '" id="f-' + data.sender.id +
        '" data-name="' + data.sender.nick_name + '" data-avatar="' + data.sender.avatar + '" data-ip="127.0.0.1">';
    _html += '<img src="' + data.sender.avatar + '">';
    _html += '<span class="user-name">' + data.sender.nick_name + '</span>';
    _html += '<span class="layui-badge" style="margin-left:5px">0</span>';
    _html += '<i class="layui-icon close" style="display:none">ဇ</i>';
    _html += '</li>';
    // 添加左侧列表
    $("#user_list").append(_html);

    // 如果没有选中人，选中第一个
    var hasActive = 0;
    $("#user_list li").each(function(){
        if($(this).hasClass('active')){
            hasActive = 1;
        }
    });

    var _html2 = '';
    _html2 += '<ul id="u-' + data.sender.id + '">';
    _html2 += '</ul>';
    // 添加主聊天面板
    $('.chat-box').append(_html2);

    if(0 == hasActive){
        $("#user_list").find('li').eq(0).addClass('active').find('span:eq(1)').removeClass('layui-badge').text('');
        $("#u-" + data.sender.id).show();
        var id = $(".layui-unselect").find('li').eq(0).data('id');
        var name = $(".layui-unselect").find('li').eq(0).data('name');
        var ip = $(".layui-unselect").find('li').eq(0).data('ip');
        var avatar = $(".layui-unselect").find('li').eq(0).data('avatar');

        // 设置当前会话用户
        $("#active-user").attr('data-id', id).attr('data-name', name).attr('data-avatar', avatar).attr('data-ip', ip);

        $("#f-user").val(name);
        $("#f-ip").val(ip);

        $.getJSON('/service/index/getCity', {ip: ip}, function(res){
            $("#f-area").val(res.data);
        });
    }

    getChatLog(data.id, 1);

    checkUser();
}

// 操作新连接用户的 dom操作
function checkUser() {

    $(".layui-unselect").find('li').unbind("click"); // 防止事件叠加
    // 切换用户
    $(".layui-unselect").find('li').bind('click', function () {
        changeUserTab($(this));
        var uid = $(this).data('id');
        var avatar = $(this).data('avatar');
        var name = $(this).data('name');
        var ip = $(this).data('ip');
        // 展示相应的对话信息
        $('.chat-box ul').each(function () {
            if ('u-' + uid == $(this).attr('id')) {
                $(this).addClass('show-chat-detail').siblings().removeClass('show-chat-detail').attr('style', '');
                return false;
            }
        });

        // 去除消息提示
        $(this).find('span').eq(1).removeClass('layui-badge').text('');

        // 设置当前会话的用户
        $("#active-user").attr('data-id', uid).attr('data-name', name).attr('data-avatar', avatar).attr('data-ip', ip);

        // 右侧展示详情
        $("#f-user").val(name);
        $("#f-ip").val(ip);
        $.getJSON('/service/index/getCity', {ip: ip}, function(res){
            $("#f-area").val(res.data);
        });

        getChatLog(uid, 1);

        wordBottom();
    });
}

// 删除用户聊天面板
function visitorLeave(data) {
    $("#f-" + data.id).remove(); // 清除左侧的用户列表
    $('#u-' + data.id).remove(); // 清除右侧的聊天详情
}

// 发送快捷语句
function sendWord(obj) {
    var msg = $(obj).data('word');
    sendMessage(msg);
}

// 获取聊天记录
function getChatLog(uid, page, flag) {

    $.getJSON('/service/index/getChatLog', {uid: uid, page: page}, function(res){
        if(1 == res.code && res.data.length > 0){

            if(res.msg == res.total){
                var _html = '<div class="layui-flow-more">没有更多了</div>';
            }else{
                var _html = '<div class="layui-flow-more"><a href="javascript:;" data-page="' + parseInt(res.msg + 1)
                    + '" onclick="getMore(this)"><cite>更多记录</cite></a></div>';
            }
            var len = res.data.length;
            for(var i = 0; i < len; i++){
                var v = res.data[len - i - 1];
                if ('mine' == v.type) {
                    _html += '<li class="whisper-chat-mine">';
                } else {
                    _html += '<li>';
                }
                _html += '<div class="whisper-chat-user">';
                _html += '<img src="' + v.from_avatar + '">';
                if ('mine' == v.type) {
                    _html += '<cite><i>' + v.time_line + '</i>' + v.from_name + '</cite>';
                } else {
                    _html += '<cite>' + v.from_name + '<i>' + v.time_line + '</i></cite>';
                }
                _html += '</div><div class="whisper-chat-text">' + replaceContent(v.content) + '</div>';
                _html += '</li>';
            }

            setTimeout(function () {
                // 滚动条自动定位到最底端
                if(typeof flag == 'undefined'){
                    $("#u-" + uid).html(_html);
                    wordBottom();
                }else{
                    $("#u-" + uid).prepend(_html);
                }

                showBigPic();
            }, 100);
        }
    });
}

// 显示大图
function showBigPic(){
    $(".layui-think-photos").on('click', function () {
        var src = this.src;
        layer.photos({
            photos: {
                data: [{
                    "alt": "大图模式",
                    "src": src
                }]
            }
            , shade: 0.5
            , closeBtn: 2
            , anim: 0
            , resize: false
            , success: function (layero, index) {

            }
        });
    });
}

// 获取更多的的记录
function getMore(obj){
    $(obj).remove();

    var page = $(obj).attr('data-page');
    var uid = $(".layui-unselect").find('li').eq(0).data('id');
    getChatLog(uid, page, 1);
}

// 打卡下班
function loginOut(){
    layer.msg("正在关闭,未咨询完的用户", {time: 50000});
    var len = $("#user_list li").length;
    var closeNum = 0;

    if(len == closeNum){
        window.location.href = '/service/login/loginOut';
    }

    $("#user_list li").each(function(){

        var uid = $(this).data('id');
        var activeUid = $("#active-user").attr('data-id');
        if(uid == activeUid){
            $("#active-user").attr('data-id', -999);
        }

        socket.send(JSON.stringify({
            type: 'closeUser', uid: uid
        }));

        $(this).parent().remove(); // 清除左侧的用户列表
        $('#u-' + uid).remove(); // 清除右侧的聊天详情

        closeNum++;
        if(closeNum == len){
            setTimeout(function(){
                window.location.href = '/service/login/loginOut';
            }, 1500); // 此处等待用户真的退出了
        }
    });
}




// 表情替换
var faces = function () {
    var alt = getFacesIcon(), arr = {};
    layui.each(alt, function (index, item) {
        arr[item] = '/static/service/js/layui/images/face/' + index + '.gif';
    });
    return arr;
}();

// 表情对应数组
function getFacesIcon() {
    return ["[微笑]", "[嘻嘻]", "[哈哈]", "[可爱]", "[可怜]", "[挖鼻]", "[吃惊]", "[害羞]", "[挤眼]", "[闭嘴]", "[鄙视]",
        "[爱你]", "[泪]", "[偷笑]", "[亲亲]", "[生病]", "[太开心]", "[白眼]", "[右哼哼]", "[左哼哼]", "[嘘]", "[衰]",
        "[委屈]", "[吐]", "[哈欠]", "[抱抱]", "[怒]", "[疑问]", "[馋嘴]", "[拜拜]", "[思考]", "[汗]", "[困]", "[睡]",
        "[钱]", "[失望]", "[酷]", "[色]", "[哼]", "[鼓掌]", "[晕]", "[悲伤]", "[抓狂]", "[黑线]", "[阴险]", "[怒骂]",
        "[互粉]", "[心]", "[伤心]", "[猪头]", "[熊猫]", "[兔子]", "[ok]", "[耶]", "[good]", "[NO]", "[赞]", "[来]",
        "[弱]", "[草泥马]", "[神马]", "[囧]", "[浮云]", "[给力]", "[围观]", "[威武]", "[奥特曼]", "[礼物]", "[钟]",
        "[话筒]", "[蜡烛]", "[蛋糕]"]
}


//fullScreen()和exitScreen()有多种实现方式，此处只使用了其中一种
//全屏
function fullScreen() {
    var element = document.documentElement;
    if (element.requestFullscreen) {
        element.requestFullscreen();
    } else if (element.msRequestFullscreen) {
        element.msRequestFullscreen();
    } else if (element.mozRequestFullScreen) {
        element.mozRequestFullScreen();
    } else if (element.webkitRequestFullscreen) {
        element.webkitRequestFullscreen();
    }
}

//退出全屏 
function exitFullscreen() {
    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.msExitFullscreen) {
        document.msExitFullscreen();
    } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
    } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
    }
}

//监听window是否全屏，并进行相应的操作,支持esc键退出
window.onresize = function() {
    var isFull=!!(document.webkitIsFullScreen || document.mozFullScreen || 
        document.msFullscreenElement || document.fullscreenElement
    );//!document.webkitIsFullScreen都为true。因此用!!
    if (isFull==false) {
        $("#exitFullScreen").css("display","none");
        $("#fullScreen").css("display","");
    }else{
        $("#exitFullScreen").css("display","");
        $("#fullScreen").css("display","none");
    }
}
