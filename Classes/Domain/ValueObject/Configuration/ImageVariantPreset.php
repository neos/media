<?php
declare(strict_types=1);

namespace Neos\Media\Domain\ValueObject\Configuration;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A Value Object for storing configuration of an Image Variant Preset
 */
final class ImageVariantPreset
{
    /**
     * @var Label
     */
    private $label;

    /**
     * @var Variant[]
     */
    private $variants = [];

    /**
     * @param Label $label
     */
    public function __construct(Label $label)
    {
        $this->label = $label;
    }

    /**
     * @param array $configuration
     * @return ImageVariantPreset
     */
    public static function fromConfiguration(array $configuration): ImageVariantPreset
    {
        $imageVariantPreset = new static(
            new Label($configuration['label'])
        );
        foreach ($configuration['variants'] as $variantIdentifier => $variantConfiguration) {
            $imageVariantPreset->variants[$variantIdentifier] = Variant::fromConfiguration($variantIdentifier, $variantConfiguration);
        }
        return $imageVariantPreset;
    }

    /**
     * @return Label
     */
    public function label(): Label
    {
        return $this->label;
    }

    /**
     * @return Variant[]
     */
    public function variants(): array
    {
        return $this->variants;
    }
}
