<?php

namespace OptivacConsent\Support;

class Constants
{
    const SOURCE_WORDPRESS = 'WORDPRESS';
    const SOURCE_BREVO     = 'BREVO';
    const SOURCE_MOBILE    = 'MOBILE';

    const TYPE_NEWSLETTER = 'NEWSLETTER';
    const TYPE_OFFERS     = 'OFFERS';

    const STATUS_GRANTED = 'GRANTED';
    const STATUS_REVOKED = 'REVOKED';

    const CACHE_TTL        = 900; 
    const CACHE_PREFIX     = 'optivac_status_';

    const OPTION_API_URL   = 'optivac_api_url';
    const OPTION_API_KEY   = 'optivac_api_key';
    const NONCE_ACTION     = 'optivac_consent_nonce';
}
