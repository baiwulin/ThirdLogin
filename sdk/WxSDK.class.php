<?php
// +---------------------------------------------------
// | 白雾林 - 用代码书写创意！ （QQ：3173343968）
// +---------------------------------------------------
// | 博客：www.baiwulin.com
// +---------------------------------------------------
// | 版权所有，严禁任何形式的逆向、反编译等侵权行为
// +---------------------------------------------------


class WxSDK extends ThinkOauth
{
    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $GetRequestCodeURL = 'https://open.weixin.qq.com/connect/qrconnect';

    /**
     * 获取access_token的api接口
     * @var string
     */
    protected $GetAccessTokenURL = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    /**
     * 获取request_code的额外参数,可在配置中修改 URL查询字符串格式
     * @var srting
     */
    protected $Authorize = 'scope=snsapi_login';

    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = 'https://api.weixin.qq.com/';

    /**
     * 组装接口调用参数 并调用接口
     * @param string $api 微博API
     * @param string $param 调用API的额外参数
     * @param string $method HTTP请求方法 默认为GET
     * @return json
     */
    public function call($api, $param = '', $method = 'GET', $multi = false)
    {
        /* 微信调用公共参数 */
        $params = array(
            'access_token' => $this->Token['access_token'],
            'openid' => $this->Token['openid']
        );

        $data = $this->http($this->url($api), $this->param($params, $param), $method);
        return json_decode($data, true);
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $result 获取access_token的方法的返回值
     */
    protected function parseToken($result, $extend)
    {
        parse_str($result, $data);
        if ($data['access_token'] && $data['expires_in']) {
            $this->Token = $data;
            return $data;
        } else {
            throw new Exception("获取微信 ACCESS_TOKEN 出错：{$result}");
        }
    }
}
