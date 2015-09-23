<?php
namespace TYPO3\Media\Tests\Functional\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Media\Domain\Model\Tag;

/**
 * Testcase for an asset repository
 *
 */
class AssetRepositoryTest extends \TYPO3\Media\Tests\Functional\AbstractTest
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var \TYPO3\Media\Domain\Repository\AssetRepository
     */
    protected $assetRepository;

    /**
     * @var \TYPO3\Media\Domain\Repository\TagRepository
     */
    protected $tagRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->prepareTemporaryDirectory();
        $this->prepareResourceManager();

        $this->assetRepository = $this->objectManager->get('TYPO3\Media\Domain\Repository\AssetRepository');
        $this->tagRepository = $this->objectManager->get('TYPO3\Media\Domain\Repository\TagRepository');
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $reflectedProperty = new \ReflectionProperty('TYPO3\Flow\Resource\ResourceManager', 'persistentResourcesStorageBaseUri');
        $reflectedProperty->setAccessible(true);
        $reflectedProperty->setValue($this->resourceManager, $this->oldPersistentResourcesStorageBaseUri);

        \TYPO3\Flow\Utility\Files::removeDirectoryRecursively($this->temporaryDirectory);
    }

    /**
     * @test
     */
    public function assetsCanBePersisted()
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/license.txt');
        $asset = new \TYPO3\Media\Domain\Model\Asset($resource);

        $this->assetRepository->add($asset);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $this->assertCount(1, $this->assetRepository->findAll());
        $this->assertInstanceOf('TYPO3\Media\Domain\Model\Asset', $this->assetRepository->findAll()->getFirst());
    }

    /**
     * @test
     */
    public function findBySearchTermReturnsFilteredResult()
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/license.txt');

        $asset1 = new \TYPO3\Media\Domain\Model\Asset($resource);
        $asset1->setTitle('foo bar');
        $asset2 = new \TYPO3\Media\Domain\Model\Asset($resource);
        $asset2->setTitle('foobar');

        $this->assetRepository->add($asset1);
        $this->assetRepository->add($asset2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $this->assertCount(2, $this->assetRepository->findAll());
        $this->assertCount(2, $this->assetRepository->findBySearchTermOrTags('foo'));
        $this->assertCount(1, $this->assetRepository->findBySearchTermOrTags(' bar'));
        $this->assertCount(0, $this->assetRepository->findBySearchTermOrTags('baz'));
    }

    /**
     * @test
     */
    public function findBySearchTermAndTagsReturnsFilteredResult()
    {
        $tag = new Tag('home');
        $this->tagRepository->add($tag);

        $resource = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/license.txt');
        $asset1 = new \TYPO3\Media\Domain\Model\Asset($resource);
        $asset1->setTitle('asset for homepage');
        $asset2 = new \TYPO3\Media\Domain\Model\Asset($resource);
        $asset2->setTitle('just another asset');
        $asset2->addTag($tag);

        $this->assetRepository->add($asset1);
        $this->assetRepository->add($asset2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $this->assertCount(2, $this->assetRepository->findBySearchTermOrTags('home', array($tag)));
        $this->assertCount(2, $this->assetRepository->findBySearchTermOrTags('homepage', array($tag)));
        $this->assertCount(1, $this->assetRepository->findBySearchTermOrTags('baz', array($tag)));
    }
}
