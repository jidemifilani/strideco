<?php

use PHPUnit\Framework\TestCase;

final class HelperFunctionsTest extends TestCase
{
    public function testFormatPriceUsesNairaSymbolAndTwoDecimals(): void
    {
        $this->assertSame('₦2,500.00', format_price(2500));
        $this->assertSame('₦0.00', format_price(0));
        $this->assertSame('₦1,234,567.89', format_price(1234567.888));
    }

    public function testSlugifyProducesUrlSafeSlugs(): void
    {
        $this->assertSame('air-stride-runner', slugify('Air Stride Runner'));
        $this->assertSame('black-governors', slugify('  Black   Governors  '));
        $this->assertSame('50-off', slugify('50% Off!!!'));
    }

    public function testSlugifyFallsBackToNaOnFullyStrippedInput(): void
    {
        $this->assertSame('n-a', slugify('%%%'));
    }

    public function testCartKeyCombinesProductIdAndSize(): void
    {
        $this->assertSame('7-42', cart_key(7, '42'));
        $this->assertSame('3-M', cart_key(3, 'M'));
    }

    public function testCartKeyIncludesVariantIdWhenGiven(): void
    {
        $this->assertSame('7-v5-42', cart_key(7, '42', 5));
        // Distinct colors of the same product/size must not collide in the cart.
        $this->assertNotSame(cart_key(7, '42', 5), cart_key(7, '42', 9));
        $this->assertSame(cart_key(7, '42'), cart_key(7, '42', null));
    }

    public function testGenerateOrderRefMatchesExpectedFormat(): void
    {
        $ref = generate_order_ref();
        // SC + 6-digit date (ymd) + 5 uppercase hex chars
        $this->assertMatchesRegularExpression('/^SC\d{6}[0-9A-F]{5}$/', $ref);
    }

    public function testGenerateOrderRefIsNotConstant(): void
    {
        $refs = array_map('generate_order_ref', range(1, 20));
        $this->assertCount(20, array_unique($refs), 'expected 20 distinct order refs, got collisions');
    }

    public function testHexShadeDarkensTowardBlack(): void
    {
        $this->assertSame('#804000', hex_shade('#FF8000', 0.5));
        $this->assertSame('#000000', hex_shade('#FF8000', 1.0));
        $this->assertSame('#ff8000', hex_shade('#FF8000', 0.0));
    }

    public function testHexTintLightensTowardWhite(): void
    {
        $this->assertSame('#ffffff', hex_tint('#FF8000', 1.0));
        $this->assertSame('#ff8000', hex_tint('#FF8000', 0.0));
        // halfway between #FF8000 and white (#FFFFFF)
        $this->assertSame('#ffc080', hex_tint('#FF8000', 0.5));
    }

    public function testMailConfiguredIsFalseWithPlaceholderCredentials(): void
    {
        // config/config.php ships with placeholder SMTP_* values by default.
        $this->assertFalse(mail_configured());
    }

    public function testShippingNudgeHtmlIsEmptyForZeroOrNegativeSubtotal(): void
    {
        $this->assertSame('', shipping_nudge_html(0));
        $this->assertSame('', shipping_nudge_html(-100));
    }

    public function testShippingNudgeHtmlShowsSuccessOnceThresholdReached(): void
    {
        $this->assertStringContainsString('free shipping', shipping_nudge_html(FREE_SHIPPING_THRESHOLD));
        $this->assertStringContainsString('shipping-nudge success', shipping_nudge_html(FREE_SHIPPING_THRESHOLD + 1000));
    }

    public function testShippingNudgeHtmlMentionsRemainingAmountBelowThreshold(): void
    {
        $html = shipping_nudge_html(FREE_SHIPPING_THRESHOLD - 5000);
        $this->assertStringContainsString(format_price(5000), $html);
    }
}
