<?php

namespace OptivacConsent\Domain\ValueObject;

class ConsentStatus
{
    private bool $newsletterGranted;
    private bool $offersGranted;
    private string $newsletterSource;
    private string $offersSource;
    private bool $newsletterModalVisible;
    private bool $offersModalVisible;

    public function __construct(array $data)
    {
        $newsletter = $data['newsletter'] ?? [];
        $offers     = $data['offers'] ?? [];

        $this->newsletterGranted      = (bool) ($newsletter['granted'] ?? false);
        $this->offersGranted          = (bool) ($offers['granted'] ?? false);
        $this->newsletterSource       = $newsletter['source'] ?? '';
        $this->offersSource           = $offers['source'] ?? '';
        $this->newsletterModalVisible = (bool) ($newsletter['modalConsentVisible'] ?? true);
        $this->offersModalVisible     = (bool) ($offers['modalConsentVisible'] ?? true);
    }

    public function newsletterGranted(): bool { return $this->newsletterGranted; }
    public function offersGranted(): bool { return $this->offersGranted; }

    public function newsletterSource(): string { return $this->newsletterSource; }
    public function offersSource(): string { return $this->offersSource; }

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
}