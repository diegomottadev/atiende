<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class WhatsAppClient
{
    private $phoneNumberId;
    private $accessToken;
    private $apiVersion;
    private $http;

    public function __construct($phoneNumberId, $accessToken, $apiVersion = 'v25.0')
    {
        $this->phoneNumberId = $phoneNumberId;
        $this->accessToken   = $accessToken;
        $this->apiVersion    = $apiVersion;
        $this->http          = new Client(['timeout' => 10.0]);
    }

    public function sendText($to, $text): array
    {
        $to = $this->normalizePhone($to);
        return $this->send([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => [
                'preview_url' => true,
                'body'        => $text,
            ],
        ]);
    }

    public function sendLocation($to, $latitude, $longitude, $name = '', $address = ''): array
    {
        $to = $this->normalizePhone($to);
        $location = [
            'longitude' => (float) $longitude,
            'latitude'  => (float) $latitude,
        ];
        // Solo incluir name/address si tienen contenido — strings vacíos muestran texto en el pin
        if ($name    !== '') $location['name']    = $name;
        if ($address !== '') $location['address'] = $address;
        return $this->send([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'location',
            'location'          => $location,
        ]);
    }

    public function sendLink($to, $url, $title)
    {
        // Cloud API auto-previewea URLs en mensajes de texto
        return $this->sendText($to, $title . "\n" . $url);
    }

    public function sendDocument($to, $pdfPath, $filename = 'ticket.pdf', $caption = '')
    {
        $to = $this->normalizePhone($to);
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/media";
        try {
            $response = $this->http->post($url, [
                'headers' => ['Authorization' => 'Bearer ' . $this->accessToken],
                'multipart' => [
                    ['name' => 'messaging_product', 'contents' => 'whatsapp'],
                    ['name' => 'type',               'contents' => 'application/pdf'],
                    ['name' => 'file',               'contents' => fopen($pdfPath, 'r'), 'filename' => $filename],
                ],
            ]);
            $mediaId = json_decode($response->getBody(), true)['id'] ?? null;
            if (!$mediaId) return;
        } catch (GuzzleException $e) {
            error_log('[WhatsAppClient] uploadMedia error: ' . $e->getMessage());
            return;
        }

        $this->send([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'document',
            'document'          => [
                'id'       => $mediaId,
                'filename' => $filename,
                'caption'  => $caption,
            ],
        ]);
    }

    public function sendInteractiveButtons($to, $bodyText, array $buttons, $footer = ''): array
    {
        $to = $this->normalizePhone($to);
        $interactive = [
            'type'   => 'button',
            'body'   => ['text' => $bodyText],
            'action' => ['buttons' => []],
        ];
        foreach ($buttons as $btn) {
            $interactive['action']['buttons'][] = [
                'type'  => 'reply',
                'reply' => ['id' => (string)$btn['id'], 'title' => (string)$btn['title']],
            ];
        }
        if ($footer) {
            $interactive['footer'] = ['text' => $footer];
        }
        return $this->send([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => $interactive,
        ]);
    }

    // Meta webhooks deliver Argentine numbers as 549XXXXXXXXXX (wa_id with mobile 9).
    // The Cloud API send endpoint expects 54XXXXXXXXXX for Argentina (9 removed).
    // All other countries: use the wa_id as-is.
    private function normalizePhone($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (preg_match('/^549(\d{8,10})$/', $phone, $m)) {
            return '54' . $m[1];
        }
        return $phone;
    }

    private function send($payload): array
    {
        if (!$this->phoneNumberId || !$this->accessToken) {
            $msg = 'Credenciales WhatsApp no configuradas para este tenant.';
            error_log('[WhatsAppClient] ' . $msg);
            return ['ok' => false, 'error' => $msg];
        }
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";
        try {
            $response = $this->http->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'json'            => $payload,
                'http_errors'     => false,
            ]);
            $body = json_decode($response->getBody(), true);
            if ($response->getStatusCode() >= 300) {
                $error = $body['error']['message'] ?? ('HTTP ' . $response->getStatusCode());
                error_log('[WhatsAppClient] Error al enviar a ' . ($payload['to'] ?? '?') . ': ' . $error);
                return ['ok' => false, 'error' => $error];
            }
            return ['ok' => true];
        } catch (GuzzleException $e) {
            error_log('[WhatsAppClient] Error al enviar a ' . ($payload['to'] ?? '?') . ': ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
