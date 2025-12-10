<?php

namespace Siaoynli\PhoneAuth\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class PhoneRule   implements ValidationRule
{
  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    if (!preg_match('/^1[3-9]\d{9}$/', $value)) {
      $fail('手机号格式不正确');
    }
  }
}
