<?php

namespace OptivacConsent\Domain\ValueObject;

class ConsentStatus
{
    private bool   $newsletterGranted;
    private bool   $offersGranted;
    private string $newsletterSource;
    private string $offersSource;
    private bool   $newsletterModalVisible;
    private bool   $offersModalVisible;
    private string $newsletterPolicyVersion;
    private string $offersPolicyVersion;

    public function __construct(array $data)
    {
        $newsletter = $data['newsletter'] ?? [];
        $offers     = $data['offers'] ?? [];

        $this->newsletterGranted       = (bool) ($newsletter['granted'] ?? false);
        $this->offersGranted           = (bool) ($offers['granted'] ?? false);
        $this->newsletterSource        = $newsletter['source'] ?? '';
        $this->offersSource            = $offers['source'] ?? '';
        $this->newsletterModalVisible  = (bool) ($newsletter['modalConsentVisible'] ?? true);
        $this->offersModalVisible      = (bool) ($offers['modalConsentVisible'] ?? true);
        $this->newsletterPolicyVersion = $newsletter['policyVersion'] ?? '';
        $this->offersPolicyVersion     = $offers['policyVersion'] ?? '';
    }

    public function newsletterGranted(): bool  { return $this->newsletterGranted; }
    public function offersGranted(): bool      { return $this->offersGranted; }
    public function newsletterSource(): string { return $this->newsletterSource; }
    public function offersSource(): string     { return $this->offersSource; }

    public function needsNewsletter(): bool
    {
        return !$this->newsletterGranted && $this->newsletterModalVisible;
    }

    public function needsOffers(): bool
    {
        return !$this->offersGranted && $this->offersModalVisible;
    }

    public function needsAnyConsent(): bool
    {
        return $this->needsNewsletter() || $this->needsOffers();
    }

    public function toArray(): array
    {
        return [
            'newsletter' => [
                'granted'             => $this->newsletterGranted,
                'source'              => $this->newsletterSource,
                'policyVersion'       => $this->newsletterPolicyVersion,
                'modalConsentVisible' => $this->newsletterModalVisible,
                'needsConsent'        => $this->needsNewsletter(),
            ],
            'offers' => [
                'granted'             => $this->offersGranted,
                'source'              => $this->offersSource,
                'policyVersion'       => $this->offersPolicyVersion,
                'modalConsentVisible' => $this->offersModalVisible,
                'needsConsent'        => $this->needsOffers(),
            ],
            'needsAnyConsent' => $this->needsAnyConsent(),
        ];
    }
}
