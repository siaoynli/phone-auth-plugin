<?php

namespace Siaoynli\PhoneAuth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhoneAuthFailed
{
  use Dispatchable, SerializesModels;

  public function __construct(
    public string $phone,
    public string $reason
  ) {}
}
