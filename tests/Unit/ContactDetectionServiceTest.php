<?php

namespace Tests\Unit;

use App\Services\ContactDetectionService;
use Tests\TestCase;

class ContactDetectionServiceTest extends TestCase
{
    private ContactDetectionService $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = app(ContactDetectionService::class);
    }

    public function test_detects_email_addresses(): void
    {
        $result = $this->detector->analyze('Reach me at john.doe@example.com for details.');
        $this->assertTrue($result['has_contact_info']);
        $this->assertContains('john.doe@example.com', $result['matches']['emails']);
    }

    public function test_detects_phone_numbers(): void
    {
        $result = $this->detector->analyze('Call me on +1 (868) 555-1234 anytime.');
        $this->assertTrue($result['has_contact_info']);
        $this->assertNotEmpty($result['matches']['phones']);
    }

    public function test_detects_social_media_links(): void
    {
        $result = $this->detector->analyze('Find me at https://instagram.com/myhandle');
        $this->assertTrue($result['has_contact_info']);
        $this->assertNotEmpty($result['matches']['social']);
    }

    public function test_clean_message_has_no_contact_info(): void
    {
        $result = $this->detector->analyze('I can start on Monday and deliver by Friday.');
        $this->assertFalse($result['has_contact_info']);
        $this->assertSame([], $result['warnings']);
    }
}
