<?php
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/Console.php';

const _mailerConf = [
    'host' => 'smtp.163.com',
    'port' => 25,
    'username' => 'zneiat@163.com',
    'showname' => 'ZneiatO',
    'password' => '123456',
    'sendto' => ['zneiat@qq.com']
];

/**
 * 发送电子邮件
 *
 * @param $subject string 标题
 * @param $body string 正文
 * @param $contentType string 内容类型
 * @return bool
 */
function _sendMail($subject, $body, $contentType='text/html')
{
    $transport = (new Swift_SmtpTransport(_mailerConf['host'], _mailerConf['port']))
        ->setUsername(_mailerConf['username'])
        ->setPassword(_mailerConf['password']);
    
    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);
    
    // Create a message
    $message = (new Swift_Message($subject))
        ->setFrom(_mailerConf['username'], _mailerConf['showname'])
        ->setTo(_mailerConf['sendto'])
        ->setContentType($contentType)
        ->setBody($body);
    
    // Send the message
    $result = $mailer->send($message);
    
    return $result ? true : false;
}

/**
 * 获取运行参数
 *
 * @param null $key
 * @return array|null
 */
function _getRunPar($key=null)
{
    if (!is_null($key))
        return isset($GLOBALS['argv'][$key]) ? $GLOBALS['argv'][$key] : null;
    
    return is_array($GLOBALS['argv']) ? $GLOBALS['argv'] : null;
}

// Renders a view file as a PHP script.
function _renderPhpFile($file, $params = [])
{
    ob_start();
    ob_implicit_flush(false);
    extract($params, EXTR_OVERWRITE);
    require($file);
    
    return ob_get_clean();
}

/**
 * 压缩html （清除换行符，清除制表符，去掉注释标记）
 *
 * @param $string
 * @return string 压缩后的
 */
function _compressHtml($string)
{
    $string = str_replace("\r\n",'',$string); // 清除换行符
    $string = str_replace("\n",'',$string); // 清除换行符
    $string = str_replace("\t",'',$string); // 清除制表符
    $pattern = ["/> *([^ ]*) *</", "/[\s]+/", "/<!--[^!]*-->/", "/\" /", "/ \"/", "'/\*[^*]*\*/'"];
    $replace = [">\\1<", " ", "", "\"", "\"", ""];
    return preg_replace($pattern, $replace, $string);
}

/**
 * Encodes special characters into HTML entities.
 * The [[\yii\base\Application::charset|application charset]] will be used for encoding.
 * @param string $content the content to be encoded
 * @param bool $doubleEncode whether to encode HTML entities in `$content`. If false,
 * HTML entities in `$content` will not be further encoded.
 * @return string the encoded content
 * @see _htmlDecode()
 * @see http://www.php.net/manual/en/function.htmlspecialchars.php
 */
function _htmlEncode($content, $doubleEncode = true)
{
    return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
}

/**
 * Decodes special HTML entities back to the corresponding characters.
 * This is the opposite of [[encode()]].
 * @param string $content the content to be decoded
 * @return string the decoded content
 * @see _htmlEncode()
 */
function _htmlDecode($content)
{
    return htmlspecialchars_decode($content, ENT_QUOTES);
}