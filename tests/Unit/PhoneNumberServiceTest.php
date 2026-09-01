<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PhoneNumberService;

class PhoneNumberServiceTest extends TestCase
{
    /**
     * Test phone number normalization.
     */
    public function test_normalization(): void
    {
        $this->assertEquals('03001234567', PhoneNumberService::normalize('0300-1234567'));
        $this->assertEquals('03001234567', PhoneNumberService::normalize('0300 1234567'));
        $this->assertEquals('03001234567', PhoneNumberService::normalize('+923001234567'));
        $this->assertEquals('03001234567', PhoneNumberService::normalize('00923001234567'));
        $this->assertEquals('03001234567', PhoneNumberService::normalize('3001234567'));
        $this->assertEquals('03001234567', PhoneNumberService::normalize('(0300)-1234567'));
        
        // Landline normalization
        $this->assertEquals('04231112233', PhoneNumberService::normalize('+924231112233'));
        $this->assertEquals('0512333444', PhoneNumberService::normalize('0092512333444'));
    }

    /**
     * Test phone number validation.
     */
    public function test_validation(): void
    {
        $this->assertTrue(PhoneNumberService::isValid('03001234567'));
        $this->assertTrue(PhoneNumberService::isValid('0300-1234567'));
        $this->assertTrue(PhoneNumberService::isValid('3001234567'));
        
        // Landline validation
        $this->assertTrue(PhoneNumberService::isValid('04231112233'));
        $this->assertTrue(PhoneNumberService::isValid('0512333444'));
        $this->assertTrue(PhoneNumberService::isValid('+924231112233'));

        // Invalid cases
        $this->assertFalse(PhoneNumberService::isValid('0300123456')); // Too short
        $this->assertFalse(PhoneNumberService::isValid('030012345678')); // Too long
        $this->assertFalse(PhoneNumberService::isValid('123456')); // Invalid format
    }

    /**
     * Test phone number formatting for display.
     */
    public function test_formatting_for_display(): void
    {
        $this->assertEquals('0300-1234567', PhoneNumberService::formatForDisplay('03001234567'));
        $this->assertEquals('0300-1234567', PhoneNumberService::formatForDisplay('0300-1234567'));
        
        // Landline formatting
        $this->assertEquals('042-31112233', PhoneNumberService::formatForDisplay('04231112233'));
        $this->assertEquals('051-2333444', PhoneNumberService::formatForDisplay('0512333444'));
    }

    /**
     * Test international conversions.
     */
    public function test_international_conversion(): void
    {
        $this->assertEquals('923001234567', PhoneNumberService::toInternational('03001234567'));
        $this->assertEquals('+923001234567', PhoneNumberService::toInternationalWithPlus('03001234567'));
        
        // Landline international conversion
        $this->assertEquals('924231112233', PhoneNumberService::toInternational('04231112233'));
        $this->assertEquals('+92512333444', PhoneNumberService::toInternationalWithPlus('0512333444'));
    }
}
