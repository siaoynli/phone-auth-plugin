<?php

namespace Siaoynli\PhoneAuth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhoneAuthSuccess
{
  use Dispatchable, SerializesModels;

  public function __construct(
    public mixed $user,
    public string $phone,
    public string $token
  ) {}
}
