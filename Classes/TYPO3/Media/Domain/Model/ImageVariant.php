<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Collections\Collection;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\Media\Domain\Model\Adjustment\ImageAdjustmentInterface;

/**
 * A user defined variant (working copy) of an original Image asset
 *
 * @Flow\Entity
 */
class ImageVariant extends Asset implements AssetVariantInterface, ImageInterface {

	use DimensionsTrait;

	/**
	 * @var \TYPO3\Media\Domain\Service\ImageService
	 * @Flow\Inject
	 */
	protected $imageService;

	/**
	 * @var \TYPO3\Media\Domain\Model\Image
	 * @ORM\ManyToOne(inversedBy="variants")
	 * @ORM\JoinColumn(nullable=false)
	 * @Flow\Validate(type="NotEmpty")
	 */
	protected $originalAsset;

	/**
	 * @var \Doctrine\Common\Collections\ArrayCollection<\TYPO3\Media\Domain\Model\Adjustment\AbstractImageAdjustment>
	 * @ORM\OneToMany(mappedBy="imageVariant", cascade={"all"}, orphanRemoval=TRUE)
	 * @ORM\OrderBy({"position" = "ASC"})
	 */
	protected $adjustments;

	/**
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "maximum"=255 })
	 */
	protected $name = '';

	/**
	 * Constructs a new Image Variant based on the given original
	 *
	 * @param Image $originalAsset The original Image asset this variant is derived from
	 */
	public function __construct(Image $originalAsset) {
		$this->originalAsset = $originalAsset;

		$this->thumbnails = new ArrayCollection();
		$this->adjustments = new ArrayCollection();
		$this->tags = new ArrayCollection();
		$this->lastModified = new \DateTime();
		$this->variants = new ArrayCollection();
	}

	/**
	 * Initialize this image variant
	 *
	 * This method will generate the resource of this asset when this object has just been newly created.
	 * We can't run renderResource() in the constructor since not all dependencies have been injected then. Generating
	 * resources lazily in the getResource() method is not feasible either, because getters will be triggered
	 * by the validation mechanism on flushing the persistence which will result in undefined behavior.
	 *
	 * We don't call refresh() here because we only want the resource to be rendered, not all other refresh actions
	 * from parent classes being executed.
	 *
	 * @param integer $initializationCause
	 * @return void
	 */
	public function initializeObject($initializationCause) {
		parent::initializeObject($initializationCause);
		if ($initializationCause === ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
			$this->renderResource();
		}
	}

	/**
	 * Returns the original image this variant is based on
	 *
	 * @return Image
	 */
	public function getOriginalAsset() {
		return $this->originalAsset;
	}

	/**
	 * Returns the resource of this image variant
	 *
	 * @return Resource
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * Refreshes this image variant: according to the added adjustments, a new image is rendered and stored as this
	 * image variant's resource.
	 *
	 * @return void
	 * @see getResource()
	 */
	public function refresh() {
		// Several refresh() calls might happen during one request. If that is the case, the Resource Manager can't know
		// that the first created resource object doesn't have to be persisted / published anymore. Thus we need to
		// delete the resource manually in order to avoid orphaned resource objects:
		if ($this->resource !== NULL) {
			$this->resourceManager->deleteResource($this->resource);
		}

		parent::refresh();
		$this->renderResource();
	}

	/**
	 * File extension of the image without leading dot.
	 * This will return the file extension of the original image as this should not be different in image variants
	 *
	 * @return string
	 */
	public function getFileExtension() {
		return $this->originalAsset->getFileExtension();
	}

	/**
	 * Returns the title of the original image
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->originalAsset->getTitle();
	}

	/**
	 * Returns the caption of the original image
	 *
	 * @return string
	 */
	public function getCaption() {
		return $this->originalAsset->getCaption();
	}

	/**
	 * Sets a name which can be used for identifying this variant
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Setting the image resource on an ImageVariant is not allowed, this method will
	 * throw a RuntimeException.
	 *
	 * @param Resource $resource
	 * @return void
	 * @throws \RuntimeException
	 */
	public function setResource(Resource $resource) {
		throw new \RuntimeException('Setting the resource on an ImageVariant is not supported.', 1366627480);
	}

	/**
	 * Setting the title on an ImageVariant is not allowed, this method will throw a
	 * RuntimeException.
	 *
	 * @param string $title
	 * @return void
	 * @throws \RuntimeException
	 */
	public function setTitle($title) {
		throw new \RuntimeException('Setting the title on an ImageVariant is not supported.', 1366627475);
	}

	/**
	 * Add a single tag to this asset
	 *
	 * @param Tag $tag
	 * @return void
	 */
	public function addTag(Tag $tag) {
		throw new \RuntimeException('Adding a tag on an ImageVariant is not supported.', 1371237593);
	}

	/**
	 * Set the tags assigned to this asset
	 *
	 * @param \Doctrine\Common\Collections\Collection $tags
	 * @return void
	 */
	public function setTags(\Doctrine\Common\Collections\Collection $tags) {
		throw new \RuntimeException('Settings tags on an ImageVariant is not supported.', 1371237597);
	}

	/**
	 * Adding variants to variants is not supported.
	 *
	 * @param ImageVariant $variant
	 * @return void
	 */
	public function addVariant(ImageVariant $variant) {
		throw new \RuntimeException('Adding variants to an ImageVariant is not supported.', 1381419461);
	}

	/**
	 * Retrieving variants from variants is not supported (no-operation)
	 *
	 * @return array
	 */
	public function getVariants() {
		return array();
	}

	/**
	 * Adds the given adjustment to the list of adjustments applied to this image variant.
	 *
	 * If an adjustment of the given type already exists, the existing one will be overridden by the new one.
	 *
	 * @param ImageAdjustmentInterface $adjustment The adjustment to apply
	 * @return void
	 */
	public function addAdjustment(ImageAdjustmentInterface $adjustment) {
		$existingAdjustmentFound = FALSE;
		$newAdjustmentClassName = TypeHandling::getTypeForValue($adjustment);

		foreach ($this->adjustments as $existingAdjustment) {
			if (TypeHandling::getTypeForValue($existingAdjustment) === $newAdjustmentClassName) {
				foreach (ObjectAccess::getGettableProperties($adjustment) as $propertyName => $propertyValue) {
					ObjectAccess::setProperty($existingAdjustment, $propertyName, $propertyValue);
				}
				$existingAdjustmentFound = TRUE;
			}
		}
		if (!$existingAdjustmentFound) {
			$this->adjustments->add($adjustment);
			$adjustment->setImageVariant($this);
			$this->adjustments = $this->adjustments->matching(new \Doctrine\Common\Collections\Criteria(NULL, array('position' => 'ASC')));
		}

		$this->lastModified = new \DateTime();
		$this->refresh();
	}

	/**
	 * @return Collection
	 */
	public function getAdjustments() {
		return $this->adjustments;
	}

	/**
	 * Tells the ImageService to render the resource of this ImageVariant according to the existing adjustments.
	 *
	 * @return void
	 */
	protected function renderResource() {
		$processedImageInfo = $this->imageService->processImage($this->originalAsset->getResource(), $this->adjustments->toArray());
		$this->resource = $processedImageInfo['resource'];
		$this->width = $processedImageInfo['width'];
		$this->height = $processedImageInfo['height'];
		$this->persistenceManager->whiteListObject($this->resource);
	}

}
