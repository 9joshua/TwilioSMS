<?php
/**
 *
 * @link https://base.nz
 *
 */

namespace Piwik\Plugins\TwilioSMS\SMSProvider;

use Exception;
use Piwik\Http;
use Piwik\Piwik;
use Piwik\Plugins\MobileMessaging\APIException;
use Piwik\Plugins\MobileMessaging\SMSProvider;

require_once PIWIK_INCLUDE_PATH . "/plugins/MobileMessaging/APIException.php";

class TwilioSMS extends SMSProvider
{
    const SOCKET_TIMEOUT = 15;
    const BASE_API_URL = 'https://api.twilio.com/2010-04-01/Accounts/';
    const MAXIMUM_FROM_LENGTH      = 11;
    const MAXIMUM_CONCATENATED_SMS = 15;

    public function getId()
    {
        return 'TwilioSMS';
    }

    public function getDescription()
    {
        return 'You can use <a target="_blank" rel="noreferrer noopener" href="https://www.twilio.com"><img src="plugins/TwilioSMS/images/Twilio.png"/></a> to send SMS Reports from Matomo.<br/>
        <ul>
            <li>First, sign up for a Twilio account at <a target="_blank" rel="noreferrer noopener" href="https://www.twilio.com">https://www.twilio.com</a>.
            </li><li>Once you have signed up, you can find your Account SID and Auth Token on your Twilio dashboard.
            </li><li>Enter your Twilio Account SID and Auth Token on this page.
            </ul>
			<br/>About Twilio: <ul>
            </li><li>Twilio is a cloud communications platform that provides a variety of APIs and services to help businesses and developers build and scale their communication systems. Twilio has been recognized as one of the fastest-growing cloud companies and has received numerous industry awards.
            </li><li>You can find more information about Twilio pricing at <a target="_blank" rel="noreferrer noopener" href="https://www.twilio.com/sms/pricing">https://www.twilio.com/sms/pricing</a>.
            </li>
        </ul>
        ';
    }

    public function getCredentialFields()
    {
        return array(
            array(
                'type'  => 'text',
                'name'  => 'AccountSID',
                'title' => 'TwilioSMS_AccountSID'
            ),
            array(
                'type'  => 'text',
                'name'  => 'AuthToken',
                'title' => 'TwilioSMS_AuthToken'
            ),
            array(
                'type'  => 'text',
                'name'  => 'SendingNumber',
                'title' => 'TwilioSMS_SendingNumber'
            ),
        );
    }

    public function verifyCredential($credentials)
    {
        $this->getCreditLeft($credentials);

        return true;
    }

    public function sendSMS($credentials, $smsText, $phoneNumber, $from)
    {
        $from = substr($from, 0, self::MAXIMUM_FROM_LENGTH);
        $smsText = self::truncate($smsText, self::MAXIMUM_CONCATENATED_SMS);
        $additionalParameters = array(
            'To'  => array($phoneNumber),
            'Body' => $smsText,
            'From'  => $credentials['SendingNumber'],
        );

        $this->issueApiCall(
            $credentials,
            $additionalParameters
        );
    }

    private function issueApiCall($credentials, $additionalParameters = array())
    {
            $timeout = self::SOCKET_TIMEOUT;
    
            $url = self::BASE_API_URL
            . $credentials['AccountSID'].'/Messages.json';

            $success = array('accepted', 'scheduled', 'queued', 'sending', 'sent');

            $Recipients = $additionalParameters['To'];

            foreach($Recipients as $Recipient){   

                $Parameters = array(
                    'To'  => $Recipient,
                    'Body' => $additionalParameters['Body'],
                    'From'  => $additionalParameters['From'],
                );
                
                try {
                    $result = Http::sendHttpRequestBy(
                        Http::getTransportMethod(),
                        $url,
                        $timeout,
                        $userAgent = null,
                        $destinationPath = null,
                        $file = null,
                        $followDepth = 0,
                        $acceptLanguage = false,
                        $acceptInvalidSslCertificate = false,
                        $byteRange = false,
                        $getExtendedInfo = false,
                        $httpMethod = 'POST',
                        $httpUserName = $credentials['AccountSID'],
                        $httpPassword = $credentials['AuthToken'],
                        $requestBody = $Parameters,
                        //$additionalParameters = null,
                    );
                } catch (Exception $e) {
                    throw new APIException($e->getMessage());
                }
    
                $result = json_decode($result, true);
           
                if (!$result || !in_array($result['status'], $success)) {
                        throw new APIException(
                            'Twilio API returned the following error message : ' . $result['error_message'].'. Status massage: '.$result['status']
                        );
                        break;
                    }
            } 
            return $result;
    }

    public function getCreditLeft($credentials)
    {

        $url = self::BASE_API_URL
            . $credentials['AccountSID'].'/Balance.json';
    
        $timeout = self::SOCKET_TIMEOUT;
        
        try {
            $result = Http::sendHttpRequestBy(
                Http::getTransportMethod(),
                $url,
                $timeout,
                $userAgent = null,
                $destinationPath = null,
                $file = null,
                $followDepth = 0,
                $acceptLanguage = false,
                $acceptInvalidSslCertificate = false,
                $byteRange = false,
                $getExtendedInfo = false,
                $httpMethod = 'GET',
                $httpUserName = $credentials['AccountSID'],
                $httpPassword = $credentials['AuthToken'],
                $requestBody = null,
            );
        } catch (Exception $e) {
            throw new APIException($e->getMessage());
        }
    
        $result = json_decode($result, true);
        return Piwik::translate('TwilioSMS_AvailableBalance', array(round($result['balance'], 2).' '.$result['currency']));
    }
}