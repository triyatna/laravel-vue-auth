<?php

namespace App\Services;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TotpService
{
    public function generateSecret(): string
    {
        return (new Google2FA())->generateSecretKey();
    }

    public function getQrSvg(string $company, string $email, string $secret): string
    {
        $g2fa = new Google2FA();
        $url = $g2fa->getQRCodeUrl($company, $email, $secret);
        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd()
        );
        return (new Writer($renderer))->writeString($url);
    }

    public function verify(string $secret, string $oneTimePassword): bool
    {
        return (new Google2FA())->verifyKey($secret, $oneTimePassword, 8);
    }
}
