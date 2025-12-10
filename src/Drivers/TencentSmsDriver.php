<?php

namespace Siaoynli\PhoneAuth\Drivers;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Sms\V20210111\Models\SendSmsRequest;
use TencentCloud\Sms\V20210111\SmsClient;
use Siaoynli\PhoneAuth\Contracts\SmsGateway;

/**
 * 腾讯云短信驱动
 * 需要安装: composer require tencentcloud/tencentcloud-sdk-php-sms
 */
class TencentSmsDriver implements SmsGateway
{
  protected $config;
  protected $client;

  public function __construct(array $config)
  {
    $this->config = $config;
    $this->initClient();
  }

  /**
   * 初始化腾讯云客户端
   */
  protected function initClient(): void
  {
    $tencentConfig = $this->config['sms']['tencent'] ?? [];

    $credential = new Credential(
      $tencentConfig['secret_id'],
      $tencentConfig['secret_key']
    );

    $httpProfile = new HttpProfile();
    $httpProfile->setEndpoint('sms.tencentcloudapi.com');

    $clientProfile = new ClientProfile();
    $clientProfile->setHttpProfile($httpProfile);

    $this->client = new SmsClient($credential, 'ap-beijing', $clientProfile);
  }

  /**
   * 发送短信
   */
  public function send(string $phone, string $code): bool
  {
    try {
      $tencentConfig = $this->config['sms']['tencent'] ?? [];

      $request = new SendSmsRequest();
      $request->setPhoneNumberSet([$phone]);
      $request->setSmsSdkAppId($tencentConfig['app_id']);
      $request->setSignName($tencentConfig['sign']);
      $request->setTemplateId($tencentConfig['template_id']);
      $request->setTemplateParamSet([$code]);

      $response = $this->client->SendSms($request);

      // 检查响应状态
      if ($response->getSendStatusSet() && count($response->getSendStatusSet()) > 0) {
        $status = $response->getSendStatusSet()[0];

        if ($status->getCode() === 'Ok') {
          \Log::info('Tencent SMS sent successfully', [
            'phone' => $phone,
            'request_id' => $response->getRequestId(),
          ]);
          return true;
        }
      }

      \Log::error('Tencent SMS send failed', [
        'phone' => $phone,
        'response' => json_encode($response),
      ]);

      return false;
    } catch (\Exception $e) {
      \Log::error('Tencent SMS exception', [
        'phone' => $phone,
        'error' => $e->getMessage(),
      ]);
      return false;
    }
  }

  public function getDriver(): string
  {
    return 'tencent';
  }
}
