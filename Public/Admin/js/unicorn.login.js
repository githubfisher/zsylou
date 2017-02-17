/**
 * Unicorn Admin Template
 * Version 2.2.0
 * Diablo9983 -> diablo9983@gmail.com
**/

var login = $('#loginform');
var recover = $('#recoverform');
var register = $('#registerform');
var login_recover = $('#loginform, #recoverform');
var login_register = $('#loginform, #registerform');
var recover_register = $('#recoverform, #registerform');
var loginbox = $('#loginbox');
var userbox = $('#user');
var animation_speed = 300;

$(document).ready(function(){

    var loc = window.location + '';
    var ee = loc.split('#');

    if(ee[1] == 'recoverform' && ee[1] != undefined){
        loginbox.css({'height':'183px'});
        $('#loginform, #registerform').css({'z-index':'100','opacity':'0.01'});
        $('#recoverform').css({'z-index':'200','opacity':'1','display':'block'});
    } else if(ee[1] = 'registerform' && ee[1] != undefined) {
        loginbox.css({'height':'280px'});
        login_recover.css({'z-index':'100','opacity':'0.01'});
        register.css({'z-index':'200','opacity':'1','display':'block'});
    }

	$('.flip-link.to-recover').click(function(){
        switch_container(recover,login_register,215);//183
	});
	$('.flip-link.to-login').click(function(){
        switch_container(login,recover_register,215);
	});
    $('.flip-link.to-register').click(function(){
        switch_container(register,login_recover,280);
    });

    $('#loginbtn').click(function(e){
        var thisForm = $(this); 
        var userinput = $('#username');
        var passinput = $('#password');
        if(userinput.val() == '' || passinput.val() == '') {
            highlight_error(userinput);
            highlight_error(passinput);
            loginbox.effect('shake');
            return false;
        } else {
            // AJAX发送用户输入信息到登录控制器
            $.post("login",{username:userinput.val(),password:passinput.val()},function(data){
                if(data.status == 1){
                    e.preventDefault();
                    loginbox.animate({'top':'+=100px','opacity':'0'},250,function(){
                        $('.user_name').text(userinput.val());
                        userbox.animate({'top':"+=75px",'opacity':'1'},250,function(){
                            setTimeout(function(){
                                if(userinput.val() == 'root' || userinput.val() == 'r00t'){
                                    window.location.href = url;
                                }else if(userinput.val() == 'weitrans999'){
                                    // 九宫格微转发后台管理用户
                                    window.location.href = weitrans;
                                }else{
                                    window.location.href = data.content;
                                }  
                            },600);
                        });
                    });
                    return true;
                }else{
                    alert(data.info);
                    window.location.reload();
                }
            });
        }       
    });
    $("#forgotbtn").click(function(e){
        var thisForm = $(this);
        var mobileinput = $('#mobile');
        var codeinput = $('#code');
        if(mobileinput.val() == '' || codeinput.val() == ''){
            highlight_error(mobileinput);
            highlight_error(codeinput);
            loginbox.effect('shake');
            return false;
        }else if(!(/^1(3|4|5|7|8)\d{9}$/.test(mobileinput.val()))){
            msg('请输入正确的手机号！',"tip","red");
            return false;
        }else{
            $.post("/index.php/admin/ForgotPassword/checkCode",{mobile:mobileinput.val(),code:codeinput.val()},function(data){
                if(data.status == 1){
                    switch_container(register,login_recover,215);
                    return true;
                }else{
                    msg(data.info,"tip","red");
                    loginbox.effect('shake');
                    return false;
                }
            });
        }
    });
    $("#registerbtn").click(function(e){
        var thisForm = $(this);
        var pwd = $('#pwd');
        var pwd2 = $('#pwd2');
        var uid = $('#uid');
        var mobile = $('#mobile');
        var regex = new RegExp('[0-9 | A-Z | a-z]{6,16}');
        if(!regex.test(pwd.val())){
            highlight_error(pwd);
            highlight_error(pwd2)
            loginbox.effect('shake');
            msg('密码须由数字或英文字符组成,6位-16位',"reset-tip","red");
            return false;
        }else if(pwd.val() != pwd2.val()){
            msg('两次输入不一致！',"reset-tip","red");
            return false;
        }else{
            $.post("/index.php/admin/ForgotPassword/reset",{pwd:pwd.val(),uid:uid.val(),mobile:mobile.val()},function(data){
                if(data.status == 1){
                    msg(data.info,"reset-tip","#428bca");
                    setTimeout(function(){
                        clearInput();
                        switch_container(login,recover_register,215);
                        msg("","reset-tip","red");
                    },1000);
                    return true;
                }else{
                    msg(data.info,"reset-tip","red");
                    return false;
                }
            });
        }
    });
});

function highlight_error(el) {
    if(el.val() == '') {
        el.parent().addClass('has-error');
    } else {
        el.parent().removeClass('has-error');
    }
}
function switch_container(to_show,to_hide,cwidth) {
    to_hide.css('z-index','100').fadeTo(animation_speed,0.01,function(){
        loginbox.animate({'height':cwidth+'px'},animation_speed,function(){
            to_show.fadeTo(animation_speed,1).css('z-index','200');
        });
    });
}