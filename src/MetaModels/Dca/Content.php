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
 * @author     Christopher Boelter <c.boelter@cogizz.de>
 * @author     Oliver Hoff <oliver@hofff.com>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/core/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\Dca;

use MetaModels\BackendIntegration\TemplateList;

/**
 * Complementary methods needed by the DCA in tl_content.
 */
class Content extends ContentElementModuleBase
{
    /**
     * Called from tl_content.onload_callback.
     *
     * @param \DC_Table $dataContainer The data container calling this method.
     *
     * @return void
     */
    public function buildCustomFilter(\DC_Table $dataContainer)
    {
        parent::buildFilterParamsFor($dataContainer, 'tl_content', 'metamodel_content');
    }

    /**
     * Fetch the template group for the current MetaModel content element.
     *
     * @param \DC_Table $objDC The data container calling this method.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getModuleTemplates(\DC_Table $objDC)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $type = $objDC->activeRecord->type;
        if ($type == 'metamodel_content') {
            $type = 'metamodel_list';
        }

        $list = new TemplateList();
        $list->setServiceContainer($GLOBALS['container']['metamodels-service-container']);

        return $list->getTemplatesForBase('ce_' . $type);
    }

    /**
     * Return the edit wizard.
     *
     * @param \DC_Table $dataContainer The data container.
     *
     * @return string
     */
    public function editMetaModel(\DC_Table $dataContainer)
    {
        return parent::editMetaModelButton($dataContainer, 'tl_content');
    }

    /**
     * Return the edit wizard.
     *
     * @param \DC_Table $dataContainer The data container.
     *
     * @return string
     */
    public function editFilterSetting(\DC_Table $dataContainer)
    {
        return parent::editFilterSettingButton($dataContainer, 'tl_content');
    }

    /**
     * Return the edit wizard.
     *
     * @param \DC_Table $dataContainer The data container.
     *
     * @return string
     */
    public function editRenderSetting(\DC_Table $dataContainer)
    {
        return parent::editRenderSettingButton($dataContainer, 'tl_content');
    }
}
