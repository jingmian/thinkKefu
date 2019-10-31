<?php /*a:1:{s:63:"E:\efarsoft\thinkKefu\application\service\view\login\index.html";i:1572142355;}*/ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>客服登录</title>
  <link rel="stylesheet" href="/static/libs/layui/css/layui.css">
  <link rel="stylesheet" href="/static/service/css/style.css">
</head>
<body class="loginPage">
  <div class="login-form">
    <div class="title">
      <img src="/static/service/img/logo.jpg" alt="thinkKefu">
      <h3>客服登录 <br><small>Customer Service</small> </h3>
    </div>
    <form class="layui-form" action="">
      <div class="layui-form-item">
        <input type="text" name="username" required  lay-verify="required" placeholder="请输入用户账号" autocomplete="off" class="layui-input">
      </div>
      <div class="layui-form-item">
        <input type="password" name="password" required  lay-verify="required" placeholder="请输入用户账号" autocomplete="off" class="layui-input">
      </div>
      <div class="layui-form-item">
        <button type="button" id="loginSubmit" class="layui-btn layui-btn-radius layui-btn-normal layui-btn-fluid" lay-submit lay-filter="loginSubmit">立即登录</button>
      </div> 

  </div>
  <script type="text/javascript" src="/static/libs/layui/layui.js"></script>
  <script type="text/javascript">
      layui.use(['form','layer'], function(){
        var $=layui.$
        ,form = layui.form
        ,layer= layui.layer;
        //监听回车
        document.onkeydown = function (e) {
            var theEvent = window.event || e;
            var code = theEvent.keyCode || theEvent.which || theEvent.charCode;
            if (code == 13) {
                $('#loginSubmit').trigger("click");
            }
        }
         //监听提交
        form.on('submit(loginSubmit)', function(data){
          $.post("<?php echo url('login/index'); ?>",data.field,function(res){
            if (res.code) {
              layer.msg(res.msg,{icon:6},function(){
                layui.data('servicePlatform', {
                  key: 'servicer'
                  ,value: res.data
                });
            
                window.location.href = res.url;
              });
            }else{
              layer.msg(res.msg,{icon:5});
            }

          });
          
          return false;
        });
        //各种基于事件的操作，下面会有进一步介绍
      });

  </script>
</body>
</html>