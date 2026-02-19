<?php

namespace Itomig\iTop\Extension\AIBase\Service;

use Combodo\iTop\Service\InterfaceDiscovery\InterfaceDiscovery;
use Itomig\iTop\Extension\AIBase\Engine\iAIEngineInterface;
use Itomig\iTop\Extension\AIBase\Exception\AIConfigurationException;
use Itomig\iTop\Extension\AIBase\Helper\AIBaseHelper;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use MetaModel;

/**
 * @copyright   Copyright (C) 2010-2025 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

class EmbeddingService
{

	protected ?iAIEngineInterface $oAIEngine;

	private EmbeddingGeneratorInterface $embeddingGenerator;

	public function __construct(?iAIEngineInterface $engine = null,?string $sModel = null,?int $iDim = null)
	{
		if(is_null($engine))
		{
			$sAIEngineName = MetaModel::GetModuleSetting(AIBaseHelper::MODULE_CODE, 'ai_engine.name', '');
			try {
				$AIEngineClass = self::GetAIEngineClass($sAIEngineName);
			}
			catch (\ReflectionException $e)
			{
				throw new AIConfigurationException('Unable to find AIEngineClass with name ="'.$sAIEngineName.'"', null, '', $e);
			}
			if(empty($AIEngineClass))
			{
				throw new AIConfigurationException('Unable to find AIEngineClass with name ="'.$sAIEngineName.'"');
			}
			$engine= $AIEngineClass::GetEngine(MetaModel::GetModuleSetting(AIBaseHelper::MODULE_CODE, 'ai_engine.configuration', ''));

		}
		$this->oAIEngine = $engine;
		$this->embeddingGenerator = $this->oAIEngine->GetEmbeddingGenerator($sModel,$iDim);
	}




	/**
	 * Retrieves and returns the class name of the configured AI engine instance, if any.
	 *
	 * @return class-string<iAIEngineInterface>|'' The class name of the AI engine, or null if no engine is configured.
	 * @throws \ReflectionException
	 */
	protected static function GetAIEngineClass(string $sAIEngineName)
	{
		$sDesiredAIEngineClass = '';
		/** @var $aAIEngines */
		$oInterfaceDiscovery = InterfaceDiscovery::GetInstance();
		$aAIEngineClasses = $oInterfaceDiscovery->FindItopClasses(iAIEngineInterface::class);
		/** @var class-string<iAIEngineInterface> $AIEngineClass */
		foreach ($aAIEngineClasses as $sAIEngineClass)
		{
			if ($sAIEngineName === $sAIEngineClass::GetEngineName())
			{
				$sDesiredAIEngineClass = $sAIEngineClass;
				break;
			}
		}
		return $sDesiredAIEngineClass;
	}

	public function GetEmbedding($sMessage) : array
	{
		return $this->embeddingGenerator->embedText($sMessage);
	}


	public function GetEmbeddingLength(): int
	{
		return $this->embeddingGenerator->getEmbeddingLength();

	}

	public function GetEmbeddingsForTexts(array $aTexts): array
	{
		$aDocuments = [];
		foreach ($aTexts as $i => $sText){
			$oDoc = new Document();
			$oDoc->content = $sText;
			$aDocuments[$i] = $oDoc;
		}

		$aDocuments = $this->embeddingGenerator->embedDocuments($aDocuments);

		$aEmbeddings = [];
		foreach ($aDocuments as $i => $oDoc){
			$aEmbeddings[$i] = $oDoc->embedding;
		}
		return $aEmbeddings;
	}

	// maybe a new function similar to getEmbeddingsForTexts but for chunked texts? 2d array input to create a document with multiple chunks?

	public function GetEmbeddingsForChunkedTexts(array $aChunkedTexts): array
	{
		$aArrayDocuments = [];
		foreach ($aChunkedTexts as $i => $aText){
			foreach ($aText as $chunkNumber => $sText){
				$oDoc = new Document();
				$oDoc->content = $sText;
				$oDoc->chunkNumber = $chunkNumber;
				$aArrayDocuments[$i][$chunkNumber] = $oDoc;
			}
		}

		foreach ($aArrayDocuments as $i => $oDoc){
			$aArrayDocuments[$i] = $this->embeddingGenerator->embedDocuments($oDoc);
		}

		$aArrayEmbeddings = [];
		foreach ($aArrayDocuments as $i => $aDocs){
			foreach ($aDocs as $chunkNumber => $oDoc){
				$aArrayEmbeddings[$i][$chunkNumber] = $oDoc->embedding;
			}
		}

		return $aArrayEmbeddings;

	}


}
