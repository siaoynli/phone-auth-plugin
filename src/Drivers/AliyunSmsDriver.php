<?php

namespace Siaoynli\PhoneAuth\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Siaoynli\PhoneAuth\Contracts\SmsGateway;

/**
 * 阿里云短信驱动（兼容版）
 * 无需安装额外SDK，使用原生HTTP请求
 */
class AliyunSmsDriver implements SmsGateway
{
  protected $config;
  protected $client;
  protected $endpoint = 'http://dysmsapi.aliyuncs.com';

  public function __construct(array $config)
  {
    $this->config = $config;
  }

  /**
   * 发送短信
   */
  public function send(string $phone, string $code, array $params = []): bool
  {
    try {
      $aliyunConfig = $this->config['sms']['aliyun'] ?? [];
      $product =  $this->config['product'] ?? env('APP_NAME');

      $params = $params ?: [
        'sign_name' => $aliyunConfig['sign_name'],
        'template_id' => 'SMS_69010036',
        'data' => [
          "code" => $code,
          "product" => $product,
        ],
      ];

      // 构建请求参数
      $requestParams = [
        'AccessKeyId' => $aliyunConfig['access_key_id'],
        'Action' => 'SendSms',
        'Format' => 'JSON',
        'PhoneNumbers' => $this->formatPhoneNumber($phone),
        'RegionId' => $aliyunConfig['region_id'] ?? 'cn-hangzhou',
        'SignName' => $params['sign_name'],
        'SignatureMethod' => 'HMAC-SHA1',
        'SignatureNonce' => uniqid(mt_rand(0, 0xffff), true),
        'SignatureVersion' => '1.0',
        'TemplateCode' => $params['template_id'],
        'TemplateParam' => json_encode($params['data'], JSON_FORCE_OBJECT),
        'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'Version' => '2017-05-25',
      ];

      // 生成签名
      $requestParams['Signature'] = $this->generateSignature($requestParams, $aliyunConfig['access_key_secret']);

      // 使用 Laravel HTTP 客户端发送请求
      $response = Http::timeout(10)
        ->connectTimeout(5)
        ->get($this->endpoint, $requestParams);

      if (!$response->successful()) {
        Log::error('阿里云短信请求失败', [
          'phone' => $phone,
          'status' => $response->status(),
          'body' => $response->body(),
        ]);
        return false;
      }

      $result = $response->json();
      // 检查响应
      if (isset($result['Code']) && $result['Code'] === 'OK') {
        Log::info('阿里云短信发送成功', [
          'phone' => $phone,
          'request_id' => $result['RequestId'],
          'biz_id' => $result['BizId'] ?? null,
        ]);
        return true;
      }

      Log::error('阿里云短信发送失败', [
        'phone' => $phone,
        'error_code' => $result['Code'] ?? 'UNKNOWN',
        'error_message' => $result['Message'] ?? 'Unknown error',
        'request_id' => $result['RequestId'] ?? null,
      ]);

      return false;
    } catch (\Exception $e) {
      Log::error('阿里云短信发送异常', [
        'phone' => $phone,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      return false;
    }
  }

  /**
   * 生成签名
   */
  protected function generateSignature(array $params, string $accessKeySecret): string
  {
    // 1. 参数排序
    ksort($params);

    // 2. 构建待签名字符串
    $stringToSign = 'GET&%2F&' . urlencode(
      http_build_query($params, '', '&', PHP_QUERY_RFC3986)
    );

    // 3. 替换特殊字符
    $stringToSign = str_replace(['%7E', '+', '%20'], ['~', '%20', '+'], $stringToSign);

    // 4. 计算HMAC-SHA1签名
    $signature = hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true);

    // 5. Base64编码
    return base64_encode($signature);
  }

  /**
   * 格式化手机号码
   */
  protected function formatPhoneNumber(string $phone): string
  {
    // 移除所有空白字符
    $phone = preg_replace('/\s+/', '', $phone);

    // 处理国际号码
    if (strpos($phone, '+') === 0) {
      // +86 转换为 86
      return substr($phone, 1);
    }

    // 检查是否包含国际区号
    if (preg_match('/^(\d{1,3})(\d{4,})$/', $phone, $matches)) {
      // 如果是 8613800138000 格式，保持原样
      return $phone;
    }

    // 默认作为国内号码（11位）
    return $phone;
  }

  /**
   * 批量发送短信（如果有需要）
   * @param array $phones 手机号数组
   * @param string $code 验证码
   * @param array $params 额外参数
   * @return array 发送结果
   */
  public function batchSend(array $phones, string $code, array $params = []): array
  {
    $results = [];
    foreach ($phones as $phone) {
      $results[$phone] = $this->send($phone, $code, $params);
    }
    return $results;
  }

  /**
   * 查询发送状态
   * @param string $bizId 发送回执ID
   * @param string $phone 手机号
   */
  public function queryStatus(string $bizId, string $phone): array
  {
    $aliyunConfig = $this->config['sms']['aliyun'] ?? [];

    $params = [
      'AccessKeyId' => $aliyunConfig['access_key_id'],
      'Action' => 'QuerySendDetails',
      'BizId' => $bizId,
      'CurrentPage' => '1',
      'PageSize' => '10',
      'PhoneNumber' => $this->formatPhoneNumber($phone),
      'SendDate' => date('Ymd'),
      'RegionId' => $aliyunConfig['region_id'] ?? 'cn-hangzhou',
      'SignatureMethod' => 'HMAC-SHA1',
      'SignatureNonce' => uniqid(mt_rand(0, 0xffff), true),
      'SignatureVersion' => '1.0',
      'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
      'Version' => '2017-05-25',
      'Format' => 'JSON',
    ];

    $params['Signature'] = $this->generateSignature($params, $aliyunConfig['access_key_secret']);

    try {
      // 使用 Laravel HTTP 客户端
      $response = Http::timeout(10)
        ->get($this->endpoint, $params);

      if (!$response->successful()) {
        Log::error('查询短信状态请求失败', [
          'biz_id' => $bizId,
          'status' => $response->status(),
        ]);
        return [];
      }

      $result = $response->json();

      if (isset($result['Code']) && $result['Code'] === 'OK') {
        return $result['SmsSendDetailDTOs']['SmsSendDetailDTO'][0] ?? [];
      }

      Log::error('查询短信状态失败', [
        'biz_id' => $bizId,
        'error_code' => $result['Code'] ?? 'UNKNOWN',
        'error_message' => $result['Message'] ?? 'Unknown error',
      ]);

      return [];
    } catch (\Exception $e) {
      Log::error('查询短信状态异常', [
        'biz_id' => $bizId,
        'error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getDriver(): string
  {
    return 'aliyun';
  }

  /**
   * 获取配置信息
   */
  public function getConfig(): array
  {
    return $this->config['sms']['aliyun'] ?? [];
  }
}
