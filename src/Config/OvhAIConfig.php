<?php
/**
 * @copyright   Copyright (C) 2010-2025 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Itomig\iTop\Extension\AIBase\Config;

use OpenAI\Contracts\ClientContract;

class OvhAIConfig
{
	public ?ClientContract $client = null;

	public string $apiKey;

	public string $url = 'https://oai.endpoints.kepler.ai.cloud.ovh.net/v1';

	public string $model;

	public function __construct(string $apiKey, string $url, string $model){
		$this->apiKey = $apiKey;
		$this->url = $url;
		$this->model = $model;
	}
}
