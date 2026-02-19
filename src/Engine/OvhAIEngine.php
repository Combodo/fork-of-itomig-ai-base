<?php
/**
 * @copyright   Copyright (C) 2010-2025 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Itomig\iTop\Extension\AIBase\Engine;

use Itomig\iTop\Extension\AIBase\Config\OvhAIConfig;
use Itomig\iTop\Extension\AIBase\Exception\NonExistingModelException;
use Itomig\iTop\Extension\AIBase\Exception\NotImplementedException;
use Itomig\iTop\Extension\AIBase\Generator\OvhAIBGEBaseEnV15EmbeddingGenerator;
use Itomig\iTop\Extension\AIBase\Generator\OvhAIBGEM3EmbeddingGenerator;
use Itomig\iTop\Extension\AIBase\Generator\OvhAIBGEMultilingualGemma2EmbeddingGenerator;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;


class OvhAIEngine extends GenericAIEngine implements iAIEngineInterface
{
	/**
	 * @inheritDoc
	 */
	public static function GetEngineName(): string
	{
		return 'OvhAI';
	}

	/**
	 * @inheritDoc
	 */
	public static function GetEngine($configuration): OvhAIEngine
	{
		$url = $configuration['url'] ?? 'https://oai.endpoints.kepler.ai.cloud.ovh.net/v1';
		$model = $configuration['model'] ?? 'BGE-M3';
		$apiKey = $configuration['api_key'] ?? '';

		return new self($url, $apiKey, $model);
	}


	public function GetCompletion($message, $systemInstruction = ''): string
	{
		throw new NotImplementedException('not implemented yet');
	}

	/**
	 * @throws \Itomig\iTop\Extension\AIBase\Exception\NonExistingModelException
	 * @throws \Exception
	 */
	public function GetEmbeddingGenerator(): EmbeddingGeneratorInterface
	{
		$config = new OvhAIConfig($this->apiKey, $this->url, $this->model);

		return match (strtolower($this->model)) {
			'bge-m3' => new OvhAIBGEM3EmbeddingGenerator($config),
			'bge-base-en-v1.5' => new OvhAIBGEBaseEnV15EmbeddingGenerator($config),
			'bge-multilingual-gemma2' => new OvhAIBGEMultilingualGemma2EmbeddingGenerator($config),
			default => throw new NonExistingModelException('Model '.$this->model.' not supported for embeddings.'),
		};
	}



}