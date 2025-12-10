<?php

namespace Siaoynli\PhoneAuth\Drivers;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Siaoynli\PhoneAuth\Contracts\SmsGateway;

/**
 * 电信
 */
class DxSmsDriver implements SmsGateway
{
  protected $config;
  protected $client;
  /**
   * 短信服务配置
   */
  private string $siid;
  private string $user;
  private string $apiKey;


  public function __construct(array $config)
  {
    $this->config = $config;
    $this->siid = $this->config['sms']['dxsms']['siid'] ?? '';
    $this->user = $this->config['sms']['dxsms']['user'] ?? '';
    $this->apiKey = $this->config['sms']['dxsms']['api_key'] ?? '';
  }

  /**
   * 发送短信
   */
  public function send(string $phone, string $code): bool
  {
    try {

      $product = $this->config['product'] ?? '杭州网';

      $content = '验证码' . $code . '，您正在登录' . $product . '，请不要泄露您的验证码给别人！';
      // 验证参数
      $this->validateParams($phone,  $content);
      // 构建请求参数
      $requestParams = $this->buildRequestParams($phone, $content);

      // 发送请求
      $response = Http::withHeaders([
        'Content-Type' => 'application/json; charset=UTF-8',
      ])->timeout(30)->post('http://smservice.zjhcsoft.com/smsservice/httpservices/capService', $requestParams);

      // 检查响应状态
      if (!$response->successful()) {
        \Log::error('DX短信发送失败（HTTP异常）', [
          'status' => $response->status(),
          'body' => $response->body(),
        ]);
        throw new InvalidArgumentException('HTTP请求失败');
      }

      // 解析响应
      $result = $response->json();

      // 检查返回码
      if ($result['retCode'] === '0000') {
        \Log::info('DX短信发送成功', [
          'transactionID' => $result['transactionID'] ?? null,
          'retCode' => $result['retCode'] ?? null,
        ]);
      } else {
        \Log::warning('DX短信发送返回错误', [
          'transactionID' => $result['transactionID'] ?? null,
          'retCode' => $result['retCode'] ?? null,
          'retMsg' => $result['retMsg'] ?? null,
        ]);
        throw new InvalidArgumentException($result['retMsg']);
      }
      return $result;
    } catch (\Exception $e) {
      \Log::error('DXSMS exception', [
        'phone' => $phone,
        'error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  public function getDriver(): string
  {
    return 'dxsms';
  }

  /**
   * 构建请求参数
   */
  private function buildRequestParams($mobile, string $content): array
  {

    // 生成时间戳和事务号
    $timeStamp = $this->generateTimeStamp();
    $transactionID = $this->generateTransactionID();
    $streamingNo = $this->generateStreamingNo();

    // 生成认证码
    $authenticator = $this->generateAuthenticator($timeStamp, $transactionID, $streamingNo);

    // 规范化手机号码
    if (is_array($mobile)) {
      $mobile = implode(',', $mobile);
    }

    return [
      'siid' => $this->siid,
      'user' => $this->user,
      'streamingNo' => $streamingNo,
      'timeStamp' => $timeStamp,
      'transactionID' => $transactionID,
      'authenticator' => $authenticator,
      'mobile' => $mobile,
      'content' => $content,
    ];
  }

  /**
   * 生成认证码
   * BASE64(MD5(timeStamp＋transactionID＋streamingNo＋接口密钥))
   */
  private function generateAuthenticator(string $timeStamp, string $transactionID, string $streamingNo): string
  {
    $source = $timeStamp . $transactionID . $streamingNo . $this->apiKey;
    $md5Hash = md5($source, true);
    return base64_encode($md5Hash);
  }

  /**
   * 生成时间戳（17位）
   * 格式：YYYYMMDDHHMMSSmmm
   */
  private function generateTimeStamp(): string
  {
    $now = microtime(true);
    $time = (int)floor($now);
    $milliseconds = (int)round(($now - $time) * 1000);

    return date('YmdHis', $time) . str_pad($milliseconds, 3, '0', STR_PAD_LEFT);
  }

  /**
   * 生成事务号（24位）
   * 可以使用时间戳 + 随机数
   */
  private function generateTransactionID(): string
  {
    return date('YmdHis') . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT) . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
  }

  /**
   * 生成流水号（24位）
   * 由SI负责生成，防止重复提交
   */
  private function generateStreamingNo(): string
  {
    return date('YmdHis') . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT) . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
  }

  /**
   * 验证参数
   */
  private function validateParams($mobile, string $content): void
  {
    // 验证手机号码
    if (empty($mobile)) {
      throw new InvalidArgumentException('手机号码不能为空');
    }

    $mobileArray = is_array($mobile) ? $mobile : explode(',', $mobile);
    if (count($mobileArray) > 50) {
      throw new InvalidArgumentException('手机号码最多支持50个');
    }

    foreach ($mobileArray as $phone) {
      $phone = trim($phone);
      if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
        throw new InvalidArgumentException('手机号码格式不正确: ' . $phone);
      }
    }

    // 验证内容
    if (empty($content)) {
      throw new InvalidArgumentException('短信内容不能为空');
    }

    if (strlen($content) > 1024) {
      throw new InvalidArgumentException('短信内容长度超过1024个字符');
    }

    // 验证配置
    if (empty($this->siid) || empty($this->user) || empty($this->apiKey)) {
      throw new InvalidArgumentException('短信服务配置不完整');
    }
  }

  /**
   * 获取返回码说明
   */
  public static function getRetCodeMessage(string $retCode): string
  {
    $messages = [
      '0000' => '调用成功',
      '0200' => '数据库异常',
      '0101' => '请求包格式不正确',
      '0401' => '流水码重复',
      '0402' => '订购关系不存在',
      '0403' => '认证失败（Ip地址不合法或秘钥不正确）',
      '0404' => '黑名单',
      '0408' => '非法关键字',
      '0501' => '非法请求参数',
      '0806' => '秘钥不存在或者加密算法有问题',
      '0901' => '其他异常',
      '0801' => '短信速率上限',
    ];

    return $messages[$retCode] ?? '未知错误';
  }
}
