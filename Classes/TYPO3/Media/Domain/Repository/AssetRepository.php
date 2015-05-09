<?php
namespace TYPO3\Media\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\QueryResultInterface;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Flow\Persistence\Doctrine\Query;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Media\Domain\Model\AssetInterface;

/**
 * A repository for Assets
 *
 * @Flow\Scope("singleton")
 */
class AssetRepository extends Repository {

	/**
	 * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
	 * interface ...
	 *
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @var array
	 */
	protected $defaultOrderings = array('lastModified' => QueryInterface::ORDER_DESCENDING);

	/**
	 * Find assets by title or given tags
	 *
	 * @param string $searchTerm
	 * @param array $tags
	 * @param AssetCollection $assetCollection*
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	public function findBySearchTermOrTags($searchTerm, array $tags = array(), AssetCollection $assetCollection = NULL) {
		$query = $this->createQuery();

		$constraints = array(
			$query->like('title', '%' . $searchTerm . '%'),
			$query->like('resource.filename', '%' . $searchTerm . '%')
		);
		foreach ($tags as $tag) {
			$constraints[] = $query->contains('tags', $tag);
		}
		$query->matching($query->logicalOr($constraints));
		$this->addAssetCollectionToQueryConstraints($query, $assetCollection);
		return $query->execute();
	}

	/**
	 * Find Assets with the given Tag assigned
	 *
	 * @param \TYPO3\Media\Domain\Model\Tag $tag
	 * @param AssetCollection $assetCollection
	 * @return QueryResultInterface
	 */
	public function findByTag(\TYPO3\Media\Domain\Model\Tag $tag, AssetCollection $assetCollection = NULL) {
		$query = $this->createQuery();
		$query->matching($query->contains('tags', $tag));
		$this->addImageVariantFilterClause($query);
		$this->addAssetCollectionToQueryConstraints($query, $assetCollection);
		return $query->execute();
	}

	/**
	 * Counts Assets with the given Tag assigned
	 *
	 * @param \TYPO3\Media\Domain\Model\Tag $tag
	 * @param AssetCollection $assetCollection
	 * @return integer
	 */
	public function countByTag(\TYPO3\Media\Domain\Model\Tag $tag, AssetCollection $assetCollection = NULL) {
		$rsm = new \Doctrine\ORM\Query\ResultSetMapping();
		$rsm->addScalarResult('c', 'c');

		if ($assetCollection === NULL) {
			$queryString = 'SELECT count(a.persistence_object_identifier) c FROM typo3_media_domain_model_asset a LEFT JOIN typo3_media_domain_model_asset_tags_join mm ON a.persistence_object_identifier = mm.media_asset WHERE mm.media_tag = ?';
		} else {
			$queryString = 'SELECT count(a.persistence_object_identifier) c FROM typo3_media_domain_model_asset a LEFT JOIN typo3_media_domain_model_asset_tags_join tagmm ON a.persistence_object_identifier = tagmm.media_asset LEFT JOIN typo3_media_domain_model_assetcollection_assets_join collectionmm ON a.persistence_object_identifier = collectionmm.media_asset WHERE tagmm.media_tag = ? AND collectionmm.media_assetcollection = ?';
		}

		$query = $this->entityManager->createNativeQuery($queryString, $rsm);
		$query->setParameter(1, $tag);
		if ($assetCollection !== NULL) {
			$query->setParameter(2, $assetCollection);
		}
		return $query->getSingleScalarResult();
	}

	/**
	 * @return QueryResultInterface
	 */
	public function findAll(AssetCollection $assetCollection = NULL) {
		$query = $this->createQuery();
		$this->addImageVariantFilterClause($query);
		$this->addAssetCollectionToQueryConstraints($query, $assetCollection);
		return $query->execute();
	}

	/**
	 * @return integer
	 */
	public function countAll() {
		$query = $this->createQuery();
		$this->addImageVariantFilterClause($query);
		return $query->count();
	}

	/**
	 * Find Assets without any tag
	 *
	 * @param AssetCollection $assetCollection
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	public function findUntagged(AssetCollection $assetCollection = NULL) {
		$query = $this->createQuery();
		$query->matching($query->isEmpty('tags'));
		$this->addImageVariantFilterClause($query);
		$this->addAssetCollectionToQueryConstraints($query, $assetCollection);
		return $query->execute();
	}

	/**
	 * Counts Assets without any tag
	 *
	 * @param AssetCollection $assetCollection
	 * @return integer
	 */
	public function countUntagged(AssetCollection $assetCollection = NULL) {
		$query = $this->createQuery();
		$query->matching($query->isEmpty('tags'));
		$this->addImageVariantFilterClause($query);
		$this->addAssetCollectionToQueryConstraints($query, $assetCollection);
		return $query->count();
	}

	/**
	 * @param AssetCollection $assetCollection
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	public function findByAssetCollection(AssetCollection $assetCollection) {
		$query = $this->createQuery();
		$this->addImageVariantFilterClause($query);
		$this->addAssetCollectionToQueryConstraints($query, $assetCollection);
		return $query->execute();
	}

	/**
	 * Count assets by asset collection
	 *
	 * @param AssetCollection $assetCollection
	 * @return integer
	 */
	public function countByAssetCollection(AssetCollection $assetCollection = NULL) {
		$query = $this->createQuery();
		$this->addImageVariantFilterClause($query);
		$this->addAssetCollectionToQueryConstraints($query, $assetCollection);
		return $query->count();
	}

	/**
	 * @param Query $query
	 * @param AssetCollection $assetCollection
	 * @return void
	 */
	protected function addAssetCollectionToQueryConstraints(Query $query, AssetCollection $assetCollection = NULL) {
		if ($assetCollection === NULL) {
			return;
		}

		$constraints = $query->getConstraint();
		$query->matching($query->logicalAnd($constraints, $query->contains('assetCollections', $assetCollection)));
	}

	/**
	 * @var Query $query
	 * @return QueryInterface
	 */
	protected function addImageVariantFilterClause(Query $query) {
		$queryBuilder = $query->getQueryBuilder();
		$queryBuilder->andWhere('e NOT INSTANCE OF TYPO3\Media\Domain\Model\ImageVariant');
		return $query;
	}

	/**
	 * @param string $sha1
	 * @return AssetInterface|NULL
	 */
	public function findOneByResourceSha1($sha1) {
		$query = $this->createQuery();
		$query->matching($query->equals('resource.sha1', $sha1, FALSE))->setLimit(1);
		return $query->execute()->getFirst();
	}
}
