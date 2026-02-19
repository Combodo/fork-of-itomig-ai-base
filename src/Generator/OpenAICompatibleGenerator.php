<?php
/**
 * @copyright   Copyright (C) 2010-2026 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Itomig\iTop\Extension\AIBase\Generator;

use Combodo\iTop\SemanticSearch\Helper\SemanticSearchLog;
use Exception;
use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Exception\MissingParameterException;
use LLPhant\OpenAIConfig;
use OpenAI;
use OpenAI\Contracts\ClientContract;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class OpenAICompatibleGenerator implements EmbeddingGeneratorInterface
{
	public ClientContract $client;

	public int $batch_size_limit = 25;

	public string $apiKey;
	protected string $uri = 'https://openrouter.ai/api/v1';

	private string $sModel = 'qwen/qwen3-embedding-8b:nitro';
	private int $iDim = 4096;

	private readonly StreamFactoryInterface
	&RequestFactoryInterface $factory;

	/**
	 * @throws Exception
	 */
	public function __construct(
		OpenAIConfig $config,
		string $sModel,
		int $iDim,
		?RequestFactoryInterface $requestFactory = null,
		?StreamFactoryInterface $streamFactory = null,
	) {
		if (! $config->apiKey) {
			throw new MissingParameterException('You have to provide an api key.');
		}
		$this->apiKey = $config->apiKey;

		if (! $config->url) {
			throw new MissingParameterException('You have to provide an url.');
		}
		$this->uri = $config->url.'/embeddings';

		$this->sModel = $sModel;
		$this->iDim = $iDim;

		if ($config->client instanceof ClientContract) {
			$this->client = $config->client;
		} else {
			$this->client = OpenAI::factory()
				->withApiKey($this->apiKey)
				->withBaseUri($config->url)
				->make();
		}

		$this->factory = new Psr17Factory(
			requestFactory: $requestFactory,
			streamFactory: $streamFactory,
		);
	}

	public function embedText(string $text): array
	{
		return [];
	}

	public function embedDocument(Document $document): Document
	{
		return new Document();
	}

	public function embedDocuments(array $documents): array
	{
		$clientForBatch = $this->createClientForBatch();
		$texts = array_map('LLPhant\Embeddings\DocumentUtils::getUtf8Data', $documents);

		// We create batches of 50 texts to avoid hitting the limit
		if ($this->batch_size_limit <= 0) {
			throw new Exception('Batch size limit must be greater than 0.');
		}

		$chunks = array_chunk($texts, $this->batch_size_limit);

		foreach ($chunks as $chunkKey => $chunk) {
			$body = [
				'model' => $this->getModelName(),
				'input' => $chunk,
			];


			$request = $this->factory->createRequest('POST', $this->uri)
				->withHeader('Content-Type', 'application/json')
				->withHeader('Accept', 'application/json')
				->withHeader('Authorization', 'Bearer '.$this->apiKey)
				->withBody($this->factory->createStream(json_encode($body, JSON_THROW_ON_ERROR)));
			$response = $clientForBatch->sendRequest($request);

			$jsonResponse = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

			if (\array_key_exists('data', $jsonResponse)) {
				foreach ($jsonResponse['data'] as $key => $oneEmbeddingObject) {
					$documents[$chunkKey * $this->batch_size_limit + $key]->embedding = $oneEmbeddingObject['embedding'];
				}
			}
		}


		return $documents;
	}

	public function getEmbeddingLength(): int
	{
		return $this->iDim;
	}

	protected function createClientForBatch(): ClientInterface
	{
		if ($this->apiKey === '' || $this->apiKey === '0') {
			throw new Exception('You have to provide an $apiKey to batch embeddings.');
		}

		return Psr18ClientDiscovery::find();
	}

	private function getModelName()
	{
		return $this->sModel;
	}
}