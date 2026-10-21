<?php

namespace Kanboard\Plugin\Rgpd\Validator;

use Kanboard\Plugin\Rgpd\Controller\RgpdPurgeController;
use Kanboard\Plugin\Rgpd\Model\PurgeScheduleModel;
use Kanboard\Validator\BaseValidator;
use SimpleValidator\Validator;
use SimpleValidator\Validators;

/**
 * Purge Settings Validator
 *
 * @package  Rgpd\Validator
 */
class PurgeSettingsValidator extends BaseValidator
{
    /**
     * Validate the purge settings
     *
     * Dates are already converted to timestamps by the controller.
     *
     * @access public
     * @param  array $values
     * @return array  $valid, $errors
     */
    public function validate(array $values)
    {
        $periods = array_keys($this->purgeScheduleModel->getPeriodList());

        $v = new Validator($values, array(
            new Validators\Required(PurgeScheduleModel::OPTION_MODE, t('Field required')),
            new Validators\InArray(
                PurgeScheduleModel::OPTION_MODE,
                array_keys($this->purgeScheduleModel->getModeList()),
                t('This value is invalid')
            ),
            new Validators\Required(PurgeScheduleModel::OPTION_FREQUENCY, t('Field required')),
            new Validators\InArray(PurgeScheduleModel::OPTION_FREQUENCY, $periods, t('This value is invalid')),
            new Validators\Required(PurgeScheduleModel::OPTION_DURATION_UNIT, t('Field required')),
            new Validators\InArray(PurgeScheduleModel::OPTION_DURATION_UNIT, $periods, t('This value is invalid')),
        ));

        $valid = $v->execute();
        $errors = $v->getErrors();

        if (! $valid) {
            return array($valid, $errors);
        }

        if ($values[PurgeScheduleModel::OPTION_MODE] === PurgeScheduleModel::MODE_DATE) {
            if (empty($values[PurgeScheduleModel::OPTION_DATE])) {
                $valid = false;
                $errors[PurgeScheduleModel::OPTION_DATE] = array(t('Invalid date'));
            } elseif ($values[PurgeScheduleModel::OPTION_DATE] > time()) {
                $valid = false;
                $errors[PurgeScheduleModel::OPTION_DATE] = array(t('The date must not be in the future'));
            }
        } else {
            $duration = (int) $values[PurgeScheduleModel::OPTION_DURATION_VALUE];

            if ($duration < 1) {
                $valid = false;
                $errors[PurgeScheduleModel::OPTION_DURATION_VALUE] = array(t('This value must be greater than %d', 0));
            }
        }

        if (! empty($values[PurgeScheduleModel::OPTION_AUTO_ENABLED])) {
            if (empty($values[RgpdPurgeController::FIELD_NEXT_RUN_DATE])) {
                $valid = false;
                $errors[RgpdPurgeController::FIELD_NEXT_RUN_DATE] = array(t('Invalid date'));
            }

            if (! array_key_exists($values[RgpdPurgeController::FIELD_NEXT_RUN_TIME], $this->helper->dt->getDayHours())) {
                $valid = false;
                $errors[RgpdPurgeController::FIELD_NEXT_RUN_TIME] = array(t('This value is invalid'));
            }
        }

        return array($valid, $errors);
    }
}
