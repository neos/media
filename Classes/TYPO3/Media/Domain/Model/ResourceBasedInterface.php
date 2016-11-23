<?php
namespace TYPO3\Media\Domain\Model;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Base interface for any class which is based on a PersistentResource.
 */
interface ResourceBasedInterface
{
    /**
     * Returns the PersistentResource
     *
     * @return \TYPO3\Flow\ResourceManagement\PersistentResource
     */
    public function getResource();

    /**
     * Refreshes this asset after the PersistentResource has been modified
     *
     * @return void
     */
    public function refresh();
}
