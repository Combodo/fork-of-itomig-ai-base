<?php
/*
 * @copyright Copyright (C) 2024, 2025 ITOMIG GmbH
 * @license http://opensource.org/licenses/AGPL-3.0
 * @author Lars Kaltefleiter <lars.kaltefleiter@itomig.de>
 * @author David Gümbel <david.guembel@itomig.de>
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with iTop. If not, see <http://www.gnu.org/licenses/>
 */

namespace Itomig\iTop\Extension\AIBase\Engine;

use IssueLog;
use Itomig\iTop\Extension\AIBase\Generator\OpenAICompatibleGenerator;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3LargeEmbeddingGenerator;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAIADA002EmbeddingGenerator;
use LLPhant\OpenAIConfig;
use LLPhant\Chat\OpenAIChat;

class OpenAIEngine extends GenericAIEngine implements iAIEngineInterface
{

	/**
	 * @inheritDoc
	 */
	public static function GetEngineName(): string
	{
		return 'OpenAI';
	}

	/**
	 * @inheritDoc
	 */
	public static function GetEngine($configuration): OpenAIEngine
	{
		$url = $configuration['url'] ?? '';
		$model = $configuration['model'] ?? 'gpt-4o-mini';
		$apiKey = $configuration['api_key'] ?? '';
		return new self($url, $apiKey, $model);
	}


	/**
	 * Ask OpenAI a question, retrieve the answer and return it in text form
	 *
	 * @param string $message
	 * @param string $systemInstruction optional - the System prompt (if a specific one is required)
	 * @return string the textual response
	 */
	public function GetCompletion($message, $systemInstruction = '') : string
	{

		IssueLog::Debug("OpenAIEngine: getCompletions() called");
		$config = new OpenAIConfig();
		$config->apiKey = $this->apiKey;
		if(!empty($this->url)) {
			$config->url = $this->url;
		}
		if(!empty($this->model)) {
			$config->model = $this->model;
		}
		$chat = new OpenAIChat($config);
        $chat->setSystemMessage ($systemInstruction);

		IssueLog::Debug("OpenAIEngine: system Message set, next step: generateText()..");
		$response = $chat->generateText($message);
		IssueLog::Debug(__METHOD__);
		IssueLog::Debug($response);
		return $response;

		// TODO error handling in LLPhant ( #2) ?
	}

	public function GetEmbeddingGenerator(?string $sModel=null,?int $iDim = null): EmbeddingGeneratorInterface
	{
		$config = new OpenAIConfig();
		$config->apiKey = $this->apiKey;
		$config->url = $this->url;

		if (!empty($this->model)) {
			$config->model = $this->model;
		}

		return match ($this->model) {
			'text-embedding-ada-002' => new OpenAIADA002EmbeddingGenerator($config),
			'text-embedding-3-small' => new OpenAI3SmallEmbeddingGenerator($config),
			'text-embedding-3-large' => new OpenAI3LargeEmbeddingGenerator($config),
			default => new OpenAICompatibleGenerator($config,$sModel,$iDim),
//			default => throw new NonExistingModelException('Model '.$this->model.' not supported for embeddings.'),
		};
	}




}
