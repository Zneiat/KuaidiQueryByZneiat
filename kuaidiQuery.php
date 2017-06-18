#!/usr/bin/env php
<?php
/**
 * KuaidiQueryByZneiat
 * @link https://github.com/Zneiat
 */
 
require 'vendor/zneiato.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

/**
 * 快递查询类
 */
class kuaidiQuery
{
    /** @var Client */
    private $_client = null;
    
    /** @var CookieJar */
    private $_cookieJar = null;
    
    // Info
    private $reqInfo = [
        'url' => 'http://www.kuaidi100.com/query',
        'method' => 'GET',
        'type' => 'type',
        'postid' => 'postid',
    ];
    
    // 配置文件名
    private $confFilePath = __CLASS__ . '.json';
    
    // 快递列表
    private $kuaidiTypes = [
	    'shunfeng' => '顺丰速递',
	    'yuantong' => '圆通快递',
	    'shentong' => '申通快递',
	    'ems' => 'EMS特快专递',
	    'huitongkuaidi' => '汇通快递',
	    'zhongtong' => '中通快递',
	    'yunda' => '韵达快递',
	    'tiantian' => '天天快递',
	    'quanfengkuaidi' => '全峰快递',
	    'quanyikuaidi' => '全一快递',
	    'rufengda' => '如风达',
	    'emsguoji' => 'EMS国际',
	    'zhaijisong' => '宅急送',
	    'debangwuliu' => '德邦物流',

	    'fedex' => 'FedEx',
	    'usps' => 'USPS',
	    'ups' => 'UPS',
	    'dhl_us' => 'DHL(美国)',
	    'dhl_de' => 'DHL(德国)',
        'TNT' => 'TNT',
	];
    
    /**
     * kuaidiQuery constructor.
     *
     * @inheritdoc
     */
    function __construct()
    {
        $this->_cookieJar = new CookieJar();
        $this->_client = new Client([
            // 'proxy'   => '127.0.0.1:1080',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
            ],
            'timeout'  => 10, // S
            'verify'  => false,
            'cookies' => $this->_cookieJar,
            'debug' => false,
        ]);
    }
    
    /**
     * 运行
     */
    public function run()
    {
        $actions = [
            'u' => '更新',
            'a' => '添加',
            'l' => '列表',
            'r' => '删除',
        ];
    
        echo "快递跟踪 (By Zneiat)\n\n";
    
        $actionKey = _getRunPar(1);
        if ($actionKey && isset($actions[$actionKey]))
        {
            try {
                call_user_func_array([$this, $actionKey], []);
            }
            catch (Exception $exception)
            {
                die('[错误] '. $exception);
            }
        }
        else
        {
            $actionListStr = "参数列表: \n";
            foreach ($actions as $key=>$val)
                $actionListStr .= $key . " ({$val}) \n";
            echo Console::wrapText($actionListStr, 2);
            echo "\n";
            echo "\n";
            echo "Windows（60分钟检测一次）：\n";
            echo "  schtasks /create /tn \"KuaidiQueryByZneiat\" /tr \"".__FILE__." u\" /sc minute /mo 60";
            echo "\n";
            echo "\n";
            echo "Linux（60分钟检测一次）：\n";
            echo "  yum install crontabs # 安装\n";
            echo "  chkconfig –level 35 crond on # 开机运行\n";
            echo "  crontab -e\n";
            echo "  */60 * * * * php ".__FILE__." u\n";
        }
    }
    
    /**
     * 更新数据
     */
    public function u()
    {
        $data = $this->dataRead();
    
        $updateKd = null;
        foreach ($data as $kdId=>$val)
        {
            $req = $this->reqData($kdId, $val['type']);
    
            if ($req->getStatusCode() != '200')
                echo('['.$kdId.'] 请求失败，状态码为: ' . $req->getStatusCode());
    
            $data = json_decode($req->getBody(), true);
            if ($data['status'] != '200')
                echo('['.$kdId.'] 请求JSON状态码为: ' . $data['status']);
    
            // 写入数据
            if ($this->dataWrite($kdId, $data['data'], $val['type']))
            {
                echo "运单号：{$kdId} - {$this->kuaidiTypes[$val['type']]} 备注："._htmlDecode($val['remind'])." 有更新\n";
                $updateKd[$kdId] = $val;
            }
            else
            {
                echo "运单号：{$kdId} - {$this->kuaidiTypes[$val['type']]} 备注："._htmlDecode($val['remind'])." 无更新\n";
            }
        }
    
        $updateKdCount = count($updateKd);
        if ($updateKdCount > 0)
        {
            echo "\n正在发送邮件提醒...\n\n";
            // 有更新则发送邮件
            $subjectStr = "跟踪快递: ".current($updateKd)['remind'].(($updateKdCount > 1) ? " 等多个" : ' ') ."物流信息有更新！";
            $updaeList = '';
            foreach ($updateKd as $kdId=>$kdVal)
                $updaeList .= "<li>运单号：{$kdId} - {$this->kuaidiTypes[$kdVal['type']]} 备注：{$kdVal['remind']}</li>";
            $bodyStr = $subjectStr.'<br/><br/>分别是：<br/><ol>'.$updaeList.'</ol>';
            $this->emailRemind($subjectStr, $bodyStr);
        }
    }
    
    /**
     * 添加跟踪
     */
    public function a()
    {
        $kdId = trim(Console::prompt('快递单号: ', [
            'required'=>true,
        ]));
        
        echo "\n";
        $kuaidiTypesStr = "快递公司列表:\n";
        foreach ($this->kuaidiTypes as $key=>$val)
            $kuaidiTypesStr .= $key . " [{$val}] \n";
        echo Console::wrapText($kuaidiTypesStr, 2);
        
        echo "\n";
        $kdType = trim(Console::prompt('快递公司: ', [
            'required'=>true,
            'validator' => function ($input)
            {
                return isset($this->kuaidiTypes[trim($input)]);
            },
        ]));
        
        echo "\n";
        $kdRemind = trim(Console::prompt('备注: ', [
            'required'=>false,
        ]));
    
        echo "\n";
        
        $req = $this->reqData($kdId, $kdType);
    
        if ($req->getStatusCode() != '200')
            die('请求失败，状态码为: ' . $req->getStatusCode());
    
        $data = json_decode($req->getBody(), true);
        if ($data['status'] != '200')
            die('响应数据: ' . $data['status'].' '.$data['message']);
    
        // 写入数据
        $this->dataWrite($kdId, $data['data'], $kdType, $kdRemind);
    
        // 发送提醒测试
        echo "\n正在发送邮件提醒...\n\n";
        $this->emailRemind("快递: {$kdId}，已添加到跟踪列表", "运单号：{$kdId} - {$this->kuaidiTypes[$kdType]} 备注：{$kdRemind} 已成功添加到快递跟踪列表<br/>接下来，您需要设置定时计划");
        
        echo "\n\n√ 运单号：{$kdId} - {$this->kuaidiTypes[$kdType]} 备注：{$kdRemind} 已添加到跟踪列表\n";
    }
    
    /**
     * 请求数据
     *
     * @param $kdId string 快递单号
     * @param $kdType string 快递类型
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function reqData($kdId, $kdType)
    {
        $req = $this->_client->request(strtoupper($this->reqInfo['method']), $this->reqInfo['url'], [
            'query' => [
                $this->reqInfo['postid'] => $kdId,
                $this->reqInfo['type'] => $kdType
            ],
            'headers' => [
                'Accept-Encoding' => 'gzip, deflate, sdch',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
        ]);
        return $req;
    }
    
    /**
     * 数据写入
     *
     * @param $kdId
     * @param $kdType
     * @param $kdRemind
     * @param $data
     *
     * @return bool 是否有更新
     */
    public function dataWrite($kdId, $data, $kdType, $kdRemind=null)
    {
        // 如果配置文件不存在
        if (!file_exists($this->confFilePath))
            touch($this->confFilePath);
    
        $write = function ($localArrHandle, $isNewData=true, $isNewAdd=false) use ($kdId, $kdType, $kdRemind, $data)
        {
            // 数据写入
            if ($isNewAdd)
            {
                // 如果是新添加的
                $localArrHandle[$kdId]['id'] = $kdId;
                $localArrHandle[$kdId]['type'] = $kdType;
                $localArrHandle[$kdId]['remind'] = _htmlEncode($kdRemind);
            }
            $localArrHandle[$kdId]['updateTime'] = time();
            if ($isNewData)
            {
                // 如果是新 download 数据
                $localArrHandle[$kdId]['isNewDownload'] = true; // 是否为新数据
                $localArrHandle[$kdId]['download'] = $data;
            }
            file_put_contents($this->confFilePath, json_encode($localArrHandle));
            return true;
        };
        
        $localArr = $this->dataRead();
        
        // 这个ID的数据是否存在
        if ($localData = $localArr[$kdId]['download'])
        {
            if ($localData != $data)
                return $write($localArr, true, false); // 不是新添加的
            else
                $write($localArr, false, false);
                return false; // 无更新
        }
        else
        {
            return $write($localArr, true, true); // 新添加的
        }
    }
    
    /**
     * 发送邮件通知
     *
     * @inheritdoc
     */
    public function emailRemind($subject='你的包裹到哪里啦？', $msg='(›´ω`‹ ) 你的包裹到哪里啦？')
    {
        $data = $this->dataRead();
        
        // 先找出新的数据
        $newDataArr = [];
        foreach ($data as $key=>$val)
        {
            if ($data[$key]['isNewDownload'])
                $newDataArr[$key] = $val;
        }
        if ((count($newDataArr)==0)&&(_getRunPar(2)!='-debug'))
            return; // 如果没有新的，那就不发了
        
        $html = _renderPhpFile('kuaidiQueryMail.php', [
            'msg' => $msg,
            'data' => $data,
            'kdTypes' => $this->kuaidiTypes,
        ]);
        
        if (_getRunPar(2)=='-debug') {
            echo _compressHtml($html);
        }
        
        // 发送邮件
        if (_sendMail($subject, $html))
        {
            echo " √ 邮件提醒发送成功\n";
        }
        else
        {
            echo " × 邮件提醒发送失败\n";
        }
    
        // 删除所有更新标签
        $this->dataRemNewTag();
        
        return;
    }
    
    /**
     * 读取数据
     *
     * @return mixed|null
     */
    public function dataRead()
    {
        if (!file_exists($this->confFilePath))
            return [];
        
        $read = file_get_contents($this->confFilePath);
        $arr = @json_decode($read, true);
        
        if (!is_array($arr))
            return [];
        
        // 数据排序
        $returnableArr = [];
        // 新的数据
        foreach ($arr as $key=>$val)
        {
            if ($arr[$key]['isNewDownload'])
                $returnableArr[$key] = $val;
        }
        // 旧的数据
        foreach ($arr as $key=>$val)
        {
            if (!$arr[$key]['isNewDownload'])
                $returnableArr[$key] = $val;
        }
        
        return $returnableArr;
    }
    
    /**
     * 删除所有新数据标签
     */
    public function dataRemNewTag()
    {
        $arr = $this->dataRead();
        
        // 删除所有新数据标签
        $newArr = [];
        foreach ($arr as $key=>$val)
        {
            $newArr[$key] = $val;
            $newArr[$key]['isNewDownload'] = false;
        }
        file_put_contents($this->confFilePath, json_encode($newArr));
    }
    
    /**
     * 删除单个数据通过运单号
     *
     * @param $byKey
     */
    public function dataRemoveOne($byKey)
    {
        $arr = $this->dataRead();
    
        if (!isset($arr[$byKey]))
            return;
        
        unset($arr[$byKey]);
        
        file_put_contents($this->confFilePath, json_encode($arr));
    }
    
    /**
     * 删除全部数据
     */
    public function dataRemoveAll()
    {
        file_put_contents($this->confFilePath, json_encode([]));
    }
    
    /**
     * 跟踪列表
     */
    public function l()
    {
        $arr = $this->dataRead();
        echo "跟踪列表 [Total: ".count($arr)."]\n";
        foreach ($arr as $key=>$val)
        {
            $lastUpdate = date('Y-m-d H:i:s', $val['updateTime']);
            echo " · 运单号：{$key} - {$this->kuaidiTypes[$val['type']]} 备注：{$val['remind']} 最后更新：{$lastUpdate}\n";
        }
    }
    
    /**
     * 跟踪删除
     */
    public function r()
    {
        $this->l();
    
        $arr = $this->dataRead();
        echo "\n";
        echo "输入想删除跟踪的运单号（输入 ALL 删除全部）: \n";
        $rKdId = trim(Console::prompt('', [
            'required'=>true,
            'validator' => function ($input) use ($arr)
            {
                return trim($input=="ALL")||isset($arr[$input]);
            },
        ]));
        
        echo "\n";
        if (!Console::confirm('确认删除 '.$rKdId.' 吗？'))
            return;
        if ($rKdId=="ALL")
        {
            if (!Console::confirm('真的要全部删除？'))
                return;
            
            $this->dataRemoveAll();
        }
        else
        {
            $this->dataRemoveOne($rKdId);
        }
        
        echo "\n√ 删除操作执行完毕";
    }
}

$kdQuery = new kuaidiQuery();
$kdQuery -> run();