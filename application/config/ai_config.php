<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Which "brain" Ezra (the floating chat assistant) uses.
|
|   'local'  = a free AI model running on this computer with Ollama (https://ollama.com).
|              Nothing leaves the computer and nothing is paid for.
|              Setup: install Ollama, then in a Command Prompt run:  ollama pull llama3.2
|              Ollama keeps running in the background and listens on port 11434.
|   'claude' = Anthropic's paid Claude API (the original v9 Ezra, with its monthly
|              spending cap, daily limit and statement of faith). Needs the API key in
|              application/config/ezra.php. Switch back by changing ai_provider only.
|
| If the local model isn't running, Ezra still answers "how do I..." questions
| from its built-in guides (libraries/Ezra_knowledge.php).
*/

$config['ai_provider'] = 'local';          // 'local' or 'claude'

// Ollama model name. 'llama3.2' (3B, about 2 GB) runs on an ordinary laptop.
// 'lugha-llama' (better at African languages) must first be imported into Ollama
// from its GGUF file with "ollama create lugha-llama -f Modelfile"; then put its name here.
$config['ai_model'] = 'llama3.2';

$config['ai_endpoint'] = 'http://localhost:11434/api/generate';

// Seconds to wait for the local model. The first question after a restart is slow
// (the model loads into memory), so keep this generous.
$config['ai_timeout'] = 120;
