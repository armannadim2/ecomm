<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\WhatsApp\Settings\WhatsAppSettings;
use App\Domain\WhatsApp\WhatsAppClient;
use App\Domain\WhatsApp\WhatsAppInbox;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Meta WhatsApp Cloud API webhook (configured in Meta → WhatsApp → Configuration):
 *   GET  verification handshake (hub.verify_token must match the settings)
 *   POST incoming messages and delivery receipts, signed with the app secret.
 * Always answers fast; the assistant runs in a queued job.
 */
class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request, WhatsAppSettings $settings): Response
    {
        $token = (string) $settings->verify_token;

        if ($request->query('hub_mode') === 'subscribe' && $token !== '' && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request, WhatsAppSettings $settings, WhatsAppClient $client, WhatsAppInbox $inbox): Response
    {
        if (! $client->validSignature($request->getContent(), $request->header('X-Hub-Signature-256'))) {
            return response('Invalid signature', 403);
        }

        if (! $settings->enabled) {
            return response('Disabled', 200);
        }

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);
                $profile = $value['contacts'][0]['profile']['name'] ?? null;

                foreach ((array) ($value['messages'] ?? []) as $message) {
                    $inbox->receive((array) $message, is_string($profile) ? $profile : null);
                }

                foreach ((array) ($value['statuses'] ?? []) as $status) {
                    if (isset($status['id'], $status['status'])) {
                        $inbox->updateStatus((string) $status['id'], (string) $status['status'], $status['errors'][0]['title'] ?? null);
                    }
                }
            }
        }

        return response('OK', 200);
    }
}
