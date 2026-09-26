<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Ezra (the AI assistant) - connection to Anthropic's Claude API.
|
| 1. Copy this file to  application/config/ezra.php  (same folder).
| 2. Paste your API key from https://console.anthropic.com  (Settings -> API keys).
| 3. Everything else (on/off, monthly spending limit, daily limit per student,
|    model, statement of faith) is set by an administrator on the Ezra page.
|
| ezra.php is listed in .gitignore, so the key never goes to GitHub. Never put
| the key anywhere else (not in the database, not in JavaScript).
*/

$config['ezra_api_key'] = '';   // e.g. 'sk-ant-api03-...'

// Only change this for testing with a stand-in server.
$config['ezra_api_url'] = 'https://api.anthropic.com/v1/messages';

// Seconds to wait for an answer before giving up.
$config['ezra_timeout'] = 90;
