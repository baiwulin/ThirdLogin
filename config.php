<?php
//定义回调URL通用的URL
define('URL_CALLBACK', Typecho_Common::url('/oauth_callback?type=', Typecho_Widget::Widget('Widget_Options')->index));
return array(
    //QQ配置
    'THINK_SDK_QQ'      => array(
        'NAME'      => 'QQ',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'qq',
    ),
    //微博配置
    'THINK_SDK_SINA'    => array(
        'NAME'      => '新浪微博',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'sina',
    ),
    //Github配置
    'THINK_SDK_GITHUB'  => array(
        'NAME'      => 'Github',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'github',
    ),
    //Google配置
    'THINK_SDK_GOOGLE'  => array(
        'NAME'      => 'Google',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'google',
    ),
    //微软配置
    'THINK_SDK_MSN'     => array(
        'NAME'      => 'MSN',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'msn',
    ),
    //百度配置
    'THINK_SDK_BAIDU'   => array(
        'NAME'      => '百度',
        'APP_KEY'       => '', //应用注册成功后分配的 APP ID
        'APP_SECRET'    => '', //应用注册成功后分配的KEY
        'CALLBACK'      => URL_CALLBACK . 'baidu',
    )
);
