<?php

namespace DrBalcony\NovaCommon\Services;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class PhoneNumberService
{
    protected PhoneNumberUtil $phoneUtil;

    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    /**
     * Validate and format the phone number to E.164 standard.
     *
     * @param string $number
     * @param string|null $region Default country region
     * @return string|null
     */
    public function formatPhoneNumber(string $number, ?string $region = null): ?string
    {
        $region = $region ?? config('nova-common.phone.default_region');
        try {
            $phoneNumber = $this->phoneUtil->parse($number, $region);
            if ($this->phoneUtil->isValidNumber($phoneNumber)) {
                return $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
            }
        } catch (NumberParseException) {
            return null;
        }

        return null;
    }

    /**
     * Determine if a phone number is valid.
     *
     * @param string $number
     * @param string|null $region
     * @return bool
     */
    public function isValidPhoneNumber(string $number, ?string $region = null): bool
    {
        return $this->formatPhoneNumber($number, $region) !== null;
    }
}