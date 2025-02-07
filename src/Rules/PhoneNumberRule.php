<?php

namespace DrBalcony\NovaCommon\Rules;


use Closure;
use DrBalcony\NovaCommon\Services\PhoneNumberService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;

class PhoneNumberRule implements ValidationRule
{
    protected string|null $region;
    protected PhoneNumberService $phoneService;

    /**
     * Create a new PhoneNumberRule instance.
     *
     * @param string|null $region Default region for phone validation (optional).
     */
    public function __construct(?string $region = null)
    {
        $this->region = $region ?? config('nova-common.phone.default_region');
        $this->phoneService = new PhoneNumberService();
    }

    /**
     * Validate that a phone number is valid.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->phoneService->isValidPhoneNumber($value, $this->region)) {
            $fail("The $attribute must be a valid phone number.");
        }
    }
}