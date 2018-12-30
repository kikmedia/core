<?php

/**
 * This file is part of MetaModels/core.
 *
 * (c) 2012-2018 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/core/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This defines a translated MetaModel.
 */
class TranslatedMetaModel extends MetaModel implements ITranslatedMetaModel
{
    /**
     * The current active language.
     *
     * @var string
     */
    private $activeLanguage;

    /**
     * The fallback language.
     *
     * @var string
     */
    private $mainLanguage;

    /**
     * Instantiate a MetaModel.
     *
     * @param array                    $arrData    The information array, for information on the available
     *                                             columns, refer to documentation of table tl_metamodel.
     * @param EventDispatcherInterface $dispatcher The event dispatcher.
     * @param Connection               $connection The database connection.
     *
     * @throws \RuntimeException When no language has been marked as main language.
     */
    public function __construct($arrData, EventDispatcherInterface $dispatcher, Connection $connection)
    {
        parent::__construct($arrData, $dispatcher, $connection);

        foreach ($this->arrData['languages'] as $languageCode => $languageData) {
            if ($languageData['isfallback']) {
                $this->mainLanguage = $languageCode;
            }
        }

        if (null === $this->mainLanguage) {
            throw new \RuntimeException('No language marked as fallback.');
        }
        // Mark fallback language as active language.
        $this->activeLanguage = $this->mainLanguage;
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguages(): array
    {
        return array_keys((array) $this->arrData['languages']);
    }

    /**
     * {@inheritDoc}
     */
    public function getMainLanguage(): string
    {
        return $this->mainLanguage;
    }

    /**
     * {@inheritDoc}
     */
    public function getLanguage(): string
    {
        return $this->activeLanguage;
    }

    /**
     * {@inheritdoc}
     */
    public function selectLanguage(string $activeLanguage): string
    {
        $previousLanguage     = $this->getLanguage();
        $this->activeLanguage = $activeLanguage;

        return $previousLanguage;
    }
}
