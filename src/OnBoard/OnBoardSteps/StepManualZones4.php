<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    INVERTUS, UAB www.invertus.eu <support@invertus.eu>
 * @copyright Copyright (c) permanent, INVERTUS, UAB
 * @license   Addons PrestaShop license limitation
 * @see       /LICENSE
 *
 *  International Registered Trademark & Property of INVERTUS, UAB
 */

namespace Invertus\dpdBaltics\OnBoard\OnBoardSteps;

use DPDBaltics;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs;
use Invertus\dpdBaltics\OnBoard\AbstractOnBoardStep;
use Invertus\dpdBaltics\OnBoard\Objects\OnBoardButton;
use Invertus\dpdBaltics\OnBoard\Objects\OnBoardFastMoveButton;
use Invertus\dpdBaltics\OnBoard\Objects\OnBoardParagraph;
use Invertus\dpdBaltics\OnBoard\Objects\OnBoardProgressBar;
use Invertus\dpdBaltics\OnBoard\Objects\OnBoardTemplateData;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

class StepManualZones4 extends AbstractOnBoardStep
{
    const FILE_NAME = 'StepManualZones4';

    public function checkIfRightStep($currentStep) {
        if ($currentStep === (new \ReflectionClass($this))->getShortName()) {
            return true;
        }

        return false;
    }

    public function takeStepData()
    {
        $templateDataObj = new OnBoardTemplateData();

        $templateDataObj->setFastMoveButton(NEW OnBoardFastMoveButton(
            Config::STEP_MAIN_3,
            Config::STEP_FAST_MOVE_BACKWARD
        ));

        if ($this->stepDataService->isAtLeastOneZoneCreated()) {
            $templateDataObj->setFastMoveButton(NEW OnBoardFastMoveButton(
                Config::STEP_MANUAL_PRODUCTS_0,
                Config::STEP_FAST_MOVE_FORWARD
            ));
        }

        $templateDataObj->setContainerClass('right-top zone');

        $templateDataObj->setParagraph(new OnBoardParagraph(
            $this->module->l('Select the country and click add.', self::FILE_NAME)
        ));
        $templateDataObj->setParagraph(new OnBoardParagraph(
            $this->module->l('If you want to include only specific ranges, but not the whole country please uncheck "all ranges" and enter from-to zip codes.', self::FILE_NAME)
        ));

        $currentProgressBarStep = Config::ON_BOARD_PROGRESS_STEP_3;

        $templateDataObj->setManualConfigProgress(
            $this->module->l(sprintf('Zones: %s/%s', $currentProgressBarStep, Config::ON_BOARD_PROGRESS_BAR_ZONES_STEPS), self::FILE_NAME)
        );

        $templateDataObj->setProgressBarObj(new OnBoardProgressBar(
            Config::ON_BOARD_PROGRESS_BAR_SECTIONS,
            $this->stepDataService->getCurrentProgressBarSection(),
            $currentProgressBarStep,
            'step'. $currentProgressBarStep . '-' . Config::ON_BOARD_PROGRESS_BAR_ZONES_STEPS
        ));

        return $templateDataObj->getTemplateData();
    }

    public function takeStepAction()
    {
        if (Tools::isSubmit('ajax')) {
            return;
        }

        $this->stepActionService->ifNotRightControllerReverseStep(
            ModuleTabs::ADMIN_ZONES_CONTROLLER,
            Config::STEP_MANUAL_ZONES_0
        );

        /** If current step is same as set in Configuration at this point it means that page was reloaded */
        $this->stepActionService->ifStepIsSameAsInConfigReverseStep(
            Config::STEP_MANUAL_ZONES_4,
            Config::STEP_MANUAL_ZONES_3
        );
    }
}
