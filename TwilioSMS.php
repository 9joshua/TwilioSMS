<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TwilioSMS;

class TwilioSMS extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return [
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
        ];
    }

    // support archiving just this plugin via core:archive
    public function getClientSideTranslationKeys(&$translationKeys) {
        $translationKeys[] = 'TwilioSMS_AccountSID';
        $translationKeys[] = 'TwilioSMS_AuthToken';
        $translationKeys[] = 'TwilioSMS_SendingNumber';
        $translationKeys[] = 'TwilioSMS_AvailableBalance';
    }
}

