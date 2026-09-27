<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The one door every Ezra question goes through: askEzra().
 *
 * config/ai_config.php decides where it goes:
 *   'local'  -> Ollama on this computer (free; POST /api/generate, stream off)
 *   'claude' -> the original Claude-based Ezra (libraries/Ezra_ai.php), with its
 *               spending cap, daily limit, statement of faith and stored history
 *
 * Switching back to Claude later is a one-word change in the config file.
 */
class Ai_provider
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('ai_config', true, true);
    }

    public function provider()
    {
        return $this->item('ai_provider', 'local') === 'claude' ? 'claude' : 'local';
    }

    public function model()
    {
        return $this->provider() === 'claude' ? 'claude' : (string) $this->item('ai_model', 'llama3.2');
    }

    protected function item($key, $default)
    {
        $v = $this->CI->config->item($key, 'ai_config');
        return $v === null || $v === '' ? $default : $v;
    }

    /**
     * Asks the configured AI. $prompt is the full prompt for the local model;
     * for Claude, $user and $question are passed to Ezra_ai::ask(), which builds
     * its own prompt from the user's data (the old Ezra, unchanged).
     *
     * Returns ['ok' => bool, 'text' => answer or '', 'error' => null|'offline'|'no_model'|'failed'|'refused'|'paused',
     *          'detail' => message for the log / Claude's own wording].
     */
    public function askEzra($prompt, array $user = null, $question = null)
    {
        if (! function_exists('curl_init')) {
            return $this->fail('offline', 'PHP cURL extension is not enabled');
        }
        return $this->provider() === 'claude' ? $this->ask_claude($user, $question !== null ? $question : $prompt) : $this->ask_local($prompt);
    }

    /* ------------------------------------------------------ OLLAMA */

    protected function ask_local($prompt)
    {
        $ch = curl_init((string) $this->item('ai_endpoint', 'http://localhost:11434/api/generate'));
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'   => $this->model(),
                'prompt'  => $prompt,
                'stream'  => false,
                'options' => ['temperature' => 0.3, 'num_ctx' => 4096, 'num_predict' => 500],
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,   // Ollama is on this machine: if it doesn't connect at once, it isn't running
            CURLOPT_TIMEOUT        => (int) $this->item('ai_timeout', 120),
        ]);
        $raw    = curl_exec($ch);
        $curlNo = curl_errno($ch);
        $curlEr = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            // 7 = couldn't connect (Ollama not started), 28 = timed out.
            return $this->fail($curlNo === 28 ? 'failed' : 'offline', 'Ollama: ' . $curlEr);
        }
        $data = json_decode($raw, true);
        if ($status === 404 || (is_array($data) && isset($data['error']) && stripos($data['error'], 'not found') !== false)) {
            return $this->fail('no_model', 'Ollama has no model "' . $this->model() . '". Run: ollama pull ' . $this->model());
        }
        if ($status !== 200 || ! is_array($data) || ! isset($data['response'])) {
            return $this->fail('failed', 'Ollama HTTP ' . $status . ': ' . mb_substr((string) $raw, 0, 300));
        }
        $text = trim((string) $data['response']);
        return $text === '' ? $this->fail('failed', 'Ollama returned an empty answer') : ['ok' => true, 'text' => $text, 'error' => null, 'detail' => null];
    }

    /* ------------------------------------------------------ CLAUDE */

    protected function ask_claude($user, $question)
    {
        if (! $user) {
            return $this->fail('failed', 'Claude mode needs the signed-in user');
        }
        $this->CI->load->library('ezra_ai');
        list($canAsk, $reason) = $this->CI->ezra_ai->availability($user);
        if (! $canAsk) {
            return ['ok' => false, 'text' => '', 'error' => 'paused', 'detail' => $reason];
        }
        $r = $this->CI->ezra_ai->ask($user, $question);
        if ($r['ok']) {
            return ['ok' => true, 'text' => $r['answer'], 'error' => null, 'detail' => null];
        }
        return ['ok' => false, 'text' => '', 'error' => $r['status'] === 'refused' ? 'refused' : 'failed', 'detail' => $r['answer']];
    }

    protected function fail($error, $detail)
    {
        return ['ok' => false, 'text' => '', 'error' => $error, 'detail' => $detail];
    }
}
