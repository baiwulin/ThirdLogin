<?php
class ThirdLogin_Widget extends Widget_Abstract_Users
{
    private $auth;
    private $third_login;
    private $referer = ''; // 来源页面
    /**
     * 风格目录
     *
     * @access private
     * @var string
     */
    private $_themeDir;

    public function __construct($request, $response, $params = null)
    {
        parent::__construct($request, $response, $params);
        $this->_themeDir = rtrim($this->options->themeFile($this->options->theme), '/') . '/';

        /** 初始化皮肤函数 */
        $functionsFile = $this->_themeDir . 'functions.php';
        if (file_exists($functionsFile)) {
            require_once $functionsFile;
            if (function_exists('themeInit')) {
                themeInit($this);
            }
        }
    }
    /**
     * 获取Oauth登录地址，重定向
     *
     * @access public
     * @param string $type 第三方登录类型
     */
    public function oauth()
    {
        $type = $this->request->get('type');
        if (is_null($type)) {
            throw new Typecho_Widget_Exception("请选择登录方式!");
        } else {
            $type = strtolower($type);
            $options = ThirdLogin_Plugin::options();

            //判断登录方式是否支持
            if (!isset($options[$type])) {
                throw new Typecho_Widget_Exception("暂不支持该登录方式! {$type}");
            }

            //加载ThinkOauth类并实例化一个对象
            require_once 'ThinkOauth.php';
            $sdk = ThinkOauth::getInstance($type);
            /**
             * 来源页面放入session
             */
            //开户session
            if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()]) {
                session_id($_COOKIE[session_name()]);
            }
            if (session_status() != PHP_SESSION_ACTIVE) {
                session_set_cookie_params(3600);
                session_start();
            }
            // 登录前页面
            $this->referer = isset($_COOKIE['ThirdLogin_Referer']) ? urldecode($_COOKIE['ThirdLogin_Referer']) : $this->request->getReferer();
            setcookie("ThirdLogin_Referer", "", time() - 3600);
            if (strpos($this->referer, $this->options->index) === 0) {
                // 站内来源页放入session
                $_SESSION['ThirdLogin_Referer'] = $this->referer;
            }
            //302重定向
            $this->response->redirect($sdk->getRequestCodeURL($type));
        }
    }
    /**
     * 第三方登录回调
     *
     * @access public
     * @param array $do POST来的用户绑定数据
     * @param string $type 第三方登录类型
     */
    public function callback()
    {
        //开户session
        if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()]) {
            session_id($_COOKIE[session_name()]);
        }
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_set_cookie_params(3600);
            session_start();
        }
        $this->auth = isset($_SESSION['__typecho_auth']) ? $_SESSION['__typecho_auth']  : array();
        $this->third_login = isset($_SESSION['__typecho_third_login']) ? $_SESSION['__typecho_third_login']  : array();
        // session内取出来源页
        $this->referer = isset($_SESSION['ThirdLogin_Referer']) ? $_SESSION['ThirdLogin_Referer'] : '';
        unset($_SESSION['ThirdLogin_Referer']);

        //仅处理来自绑定界面POST提交的数据，第三方回调会跳过
        if ($this->request->isPost()) {
            $do = $this->request->get('do');
            if (!in_array($do, array('bind','reg'))) {
                throw new Typecho_Widget_Exception("错误数据！");
            }

            if (!isset($this->auth['openid']) || !isset($this->auth['type'])) {
                $this->response->redirect(empty($this->referer) ? $this->options->index : $this->referer);
            }
            $func = 'doCallback'.ucfirst($do);
            $this->$func();
            unset($_SESSION['__typecho_auth']);
            unset($_SESSION['__typecho_third_login']);
            $this->response->redirect(empty($this->referer) ? $this->options->index : $this->referer);
        }

        //第三方登录回调处理
        $options = ThirdLogin_Plugin::options();
        $third_login = array();
        if (empty($this->auth)) {
            $code = $this->request->get('code', '');
            $this->auth['type'] = $this->request->get('type', '');

            if (empty($code) || empty($this->auth['type']) || !isset($options[$this->auth['type']])) {
                //缺少code、type、未开启type，直接跳主页
                $this->response->redirect($this->options->index);
            }
            //转小写
            $type = $this->auth['type'] = strtolower($this->auth['type']);
            //加载ThinkOauth类并实例化一个对象
            require_once 'ThinkOauth.php';
            $sdk = ThinkOauth::getInstance($type);
            //请求接口(返回值包含openid)
            $token = $sdk->getAccessToken($type, $code);
            if (is_array($token)) {
                //获取第三方账号数据
                $user_info = $this->$type($token);
                $third_login = array(
                    'uid'           =>  0,
                    'openid'        =>  $token['openid'],
                    'access_token'  =>  $token['access_token'],
                    'expires_in'    =>  isset($token['expires_in']) ? $this->options->gmtTime+$token['expires_in']: 0,
                    'gender'        =>  isset($user_info['gender']) ? $user_info['gender'] : 0,
                    'head_img'      =>  $user_info['head_img'],
                    'name'          =>  $user_info['name'],
                    'nickname'      =>  $user_info['nickname'],
                    'type'          =>  $type,
                );
                //获取openid
                $this->auth['openid'] = $token['openid'];
                $this->auth['nickname'] = $user_info['nickname'];
            } else {
                $this->response->redirect($this->options->index);
            }
        }
        //登录状态
        if ($this->user->hasLogin()) {
            //UUID会员的原始ID
            $this->auth['uuid'] = $this->user->uid;

            //直接绑定第三方账号
            $this->bindUser($this->user->uid, $third_login, $this->auth['type']);
            //提示，并跳转
            $this->widget('Widget_Notice')->set(array('成功绑定账号!'));
            $this->response->redirect(empty($this->referer) ? $this->options->index : $this->referer);
        } else {
            //未登录状态，查询第三方账号的绑定关系
            $isConnect = $this->findConnectUser($third_login, $this->auth['type']);
            if ($isConnect) {
                //已经绑定，直接登录
                $this->useUidLogin($isConnect['uid']);
                //提示，并跳转
                $this->widget('Widget_Notice')->set(array('已成功登陆!'));
                $this->response->redirect(empty($this->referer) ? $this->options->index : $this->referer);
            }

            //未登录状态且未绑定，控制显示绑定界面
            $custom = $this->options->plugin('ThirdLogin')->custom;
            if (!$custom && !empty($this->auth['nickname'])) {
                $dataStruct = array(
                    'screenName'=>  $this->auth['nickname'],
                    'created'   =>  $this->options->gmtTime,
                    'group'     =>  'subscriber'
                );
                //新注册账号
                $uid = $this->regConnectUser($dataStruct, $third_login);
                if ($uid) {
                    $this->widget('Widget_Notice')->set(array('已成功注册并登陆!'));
                } else {
                    $this->widget('Widget_Notice')->set(array('注册用户失败!'), 'error');
                }
                $this->response->redirect(empty($this->referer) ? $this->options->index : $this->referer);
            } else {
                //用户绑定界面
                if (!isset($_SESSION['__typecho_auth'])) {
                    $_SESSION['__typecho_auth'] = $this->auth;
                    $_SESSION['__typecho_third_login'] = $third_login;
                }
                //未绑定，引导用户到绑定界面
                $this->render('callback.php');
            }
        }
    }
    //绑定已有用户
    protected function doCallbackBind()
    {
        $name = $this->request->get('name');
        $password = $this->request->get('password');

        if (empty($name) || empty($password)) {
            $this->widget('Widget_Notice')->set(array('帐号或密码不能为空!'), 'error');
            $this->response->goBack();
        }
        $isLogin = $this->user->login($name, $password);
        if ($isLogin) {
            //UUID会员的原始ID
            $this->auth['uuid'] = $this->user->uid;

            $this->widget('Widget_Notice')->set(array('已成功绑定并登陆!'));
            $this->bindUser($this->user->uid, $this->third_login, $this->auth['type']);
        } else {
            $this->widget('Widget_Notice')->set(array('帐号或密码错误!'), 'error');
            $this->response->goBack();
        }
    }
    //注册新用户
    protected function doCallbackReg()
    {
        $url = $this->request->get('url');

        $validator = new Typecho_Validate();
        $validator->addRule('mail', 'required', _t('必须填写电子邮箱'));
        $validator->addRule('mail', array($this, 'mailExists'), _t('电子邮箱地址已经存在'));
        $validator->addRule('mail', 'email', _t('电子邮箱格式错误'));
        $validator->addRule('mail', 'maxLength', _t('电子邮箱最多包含200个字符'), 200);

        $validator->addRule('screenName', 'required', _t('必须填写昵称'));
        $validator->addRule('screenName', 'xssCheck', _t('请不要在昵称中使用特殊字符'));
        $validator->addRule('screenName', array($this, 'screenNameExists'), _t('昵称已经存在'));

        if ($url) {
            $validator->addRule('url', 'url', _t('个人主页地址格式错误'));
        }

        /** 截获验证异常 */
        if ($error = $validator->run($this->request->from('mail', 'screenName', 'url'))) {
            /** 设置提示信息 */
            $this->widget('Widget_Notice')->set($error);
            $this->response->goBack();
        }

        $dataStruct = array(
            'mail'      =>  $this->request->mail,
            'screenName'=>  $this->request->screenName,
            'created'   =>  $this->options->gmtTime,
            'group'     =>  'subscriber'
        );
        $uid = $this->regConnectUser($dataStruct, $this->third_login);
        if ($uid) {
            $this->widget('Widget_Notice')->set(array('已成功注册并登陆!'));
        }
    }

    protected function regConnectUser($data, $third_login)
    {
        $insertId = $this->insert($data);
        if ($insertId) {
            //UUID会员的原始ID
            $this->auth['uuid'] = $insertId;

            $this->bindUser($insertId, $third_login, $this->auth['type']);
            $this->useUidLogin($insertId);
            return $insertId;
        } else {
            return false;
        }
    }

    //处理用户与第三方账号的绑定关系（逻辑复杂）
    // 同一用户，可以绑定15种不同的登录方式！但是，同类型的第三方账号仅可绑定一个！
    protected function bindUser($uid, $third_login, $type)
    {
        $third_login['uid'] = $uid;
        if (isset($this->auth['uuid'])) {
            $third_login['uuid'] = $this->auth['uuid'];
        }
        //查询当前登录的账号是否绑定？
        $connect = $this->db->fetchRow($this->db->select()
            ->from('table.third_login')
            ->where('uid = ?', $uid)
            ->where('type = ?', $type)
            ->limit(1));
        if (empty($connect)) {
            //未绑定
            $oauthRow = $this->findConnectUser($third_login, $type);
            if ($oauthRow) {
                //已存在第三方账号，更新绑定关系
                $this->db->query($this->db
                ->update('table.third_login')
                ->rows(array('uid' => $uid))
                ->where('openid = ?', $third_login['openid'])
                ->where('type = ?', $type));
            } else {
                //未绑定，插入数据并绑定
                $this->db->query($this->db->insert('table.third_login')->rows($third_login));
            }
        } else {
            //已绑定，判断更新条件，避免绑定错乱（同类型的第三方账号，用户只能绑定一个）
            if ($connect['openid'] == $third_login['openid']) {
                ###更新资料tudo
            } else {
                ###换绑tudo
            }
        }
    }
    //查找第三方账号
    protected function findConnectUser($third_login, $type)
    {
        $user = $this->db->fetchRow($this->db->select()
            ->from('table.third_login')
            ->where('openid = ?', $third_login['openid'])
            ->where('type = ?', $type)
            ->limit(1));
        return empty($user)? 0 : $user;
    }
    //使用用户uid登录
    protected function useUidLogin($uid, $expire = 0)
    {
        $authCode = function_exists('openssl_random_pseudo_bytes') ?
        bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Typecho_Common::randString(20));
        $user = array('uid'=>$uid,'authCode'=>$authCode);

        Typecho_Cookie::set('__typecho_uid', $uid, $expire);
        Typecho_Cookie::set('__typecho_authCode', Typecho_Common::hash($authCode), $expire);

        //更新最后登录时间以及验证码
        $this->db->query($this->db
            ->update('table.users')
            ->expression('logged', 'activated')
            ->rows(array('authCode' => $authCode))
            ->where('uid = ?', $uid));
        $this->db->query($this->db
            ->update('table.third_login')
            ->rows(array('datetime' => date("Y-m-d H:i:s", time())))
            ->where('uid = ?', $uid));
    }

    public function render($themeFile)
    {
        /** 文件不存在 */
        if (!is_file(__DIR__ . '/' . $themeFile)) {
            Typecho_Common::error(500);
        }
        /** 输出模板 */
        require_once (__DIR__ . '/' . $themeFile);
    }
    /**
     * 获取主题文件
     *
     * @access public
     * @param string $fileName 主题文件
     * @return void
     */
    public function need($fileName)
    {
        require $this->_themeDir . $fileName;
    }

    //登录成功，获取微信用户信息
    public function wx($token)
    {
        $qq = ThinkOauth::getInstance('wx', $token);
        $data = $qq->call('sns/userinfo');
        if (empty($data['errcode'])) {
            $userInfo['name'] = $data['nickname'];
            $userInfo['nickname'] = $data['nickname'];
            $userInfo['head_img'] = $data['headimgurl'];

            if ($data['sex'] == 1) {
                $userInfo['gender'] = 1;
            } elseif ($data['sex'] == 2) {
                $userInfo['gender'] = 2;
            } else {
                $userInfo['gender'] = 0;
            }
            return $userInfo;
        } else {
            $this->widget('Widget_Notice')->set(array("获取用户信息失败：{$data['msg']}"), 'error');
        }
    }

    //登录成功，获取QQ用户信息
    public function qq($token)
    {
        $qq = ThinkOauth::getInstance('qq', $token);
        $data = $qq->call('user/get_user_info');
        if ($data['ret'] == 0) {
            $userInfo['name'] = $data['nickname'];
            $userInfo['nickname'] = $data['nickname'];
            $userInfo['head_img'] = $data['figureurl_2'];

            if ($data['gender'] == '男') {
                $userInfo['gender'] = 1;
            } elseif ($data['gender'] == '女') {
                $userInfo['gender'] = 2;
            } else {
                $userInfo['gender'] = 0;
            }
            return $userInfo;
        } else {
            $this->widget('Widget_Notice')->set(array("获取用户信息失败：{$data['msg']}"), 'error');
        }
    }

    //登录成功，获取微博用户信息
    public function sina($token)
    {
        $sina = ThinkOauth::getInstance('sina', $token);
        $data = $sina->call('users/show', "uid={$sina->openid()}");
        if ($data['error_code'] == 0) {
            $userInfo['name'] = $data['name'];
            $userInfo['nickname'] = $data['screen_name'];
            $userInfo['head_img'] = $data['avatar_large'];
            $userInfo['gender'] = 0;
            return $userInfo;
        } else {
            //throw_exception("获取微博用户信息失败：{$data['error']}");
        }
    }

    //登录成功，获取Github用户信息
    public function github($token)
    {
        $github = ThinkOauth::getInstance('github', $token);
        $data   = $github->call('user');
        if (empty($data['code'])) {
            $userInfo['name'] = $data['login'];
            $userInfo['nickname'] = $data['name'];
            $userInfo['head_img'] = $data['avatar_url'];
            $userInfo['gender'] = 0;
            return $userInfo;
        } else {
            //throw_exception("获取Github用户信息失败：{$data}");
        }
    }

    //登录成功，获取Google用户信息
    public function google($token)
    {
        $google = ThinkOauth::getInstance('google', $token);
        $data   = $google->call('userinfo');
        if (!empty($data['id'])) {
            $userInfo['name'] = $data['name'];
            $userInfo['nickname'] = $data['name'];
            $userInfo['head_img'] = $data['picture'];
            $userInfo['gender'] = 0;
            return $userInfo;
        } else {
            //throw_exception("获取Google用户信息失败：{$data}");
        }
    }

    //登录成功，获取msn用户信息
    public function msn($token)
    {
        $msn  = ThinkOauth::getInstance('msn', $token);
        $data = $msn->call('me');
        if (!empty($data['id'])) {
            $userInfo['name'] = $data['name'];
            $userInfo['nickname'] = $data['name'];
            $userInfo['head_img'] = '微软暂未提供头像URL，请通过 me/picture 接口下载';
            $userInfo['gender'] = 0;
            return $userInfo;
        } else {
            //throw_exception("获取msn用户信息失败：{$data}");
        }
    }

    //登录成功，获取百度用户信息
    public function baidu($token)
    {
        $baidu = ThinkOauth::getInstance('baidu', $token);
        $data  = $baidu->call('passport/users/getLoggedInUser');
        if (!empty($data['uid'])) {
            $userInfo['name'] = $data['uid'];
            $userInfo['nickname'] = $data['uname'];
            $userInfo['head_img'] = "http://tb.himg.baidu.com/sys/portrait/item/{$data['portrait']}";
            $userInfo['gender'] = 0;
            return $userInfo;
        } else {
            //throw_exception("获取百度用户信息失败：{$data['error_msg']}");
        }
    }
}
