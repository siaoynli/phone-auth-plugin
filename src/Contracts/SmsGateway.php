<?php

namespace Siaoynli\PhoneAuth\Contracts;

interface SmsGateway
{
  /**
   * 发送短信
   */
  public function send(string $phone, string $code): bool;

  /**
   * 获取驱动名称
   */
  public function getDriver(): string;
}
